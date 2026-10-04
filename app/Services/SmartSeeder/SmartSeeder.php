<?php

namespace App\Services\SmartSeeder;

use App\Models\AiSetting;
use App\Services\CrudGenerator\FieldSpec;
use App\Services\CrudGenerator\FileOperation;
use App\Services\CrudGenerator\Manifest;
use App\Services\CrudGenerator\ModuleSpec;
use App\Services\CrudGenerator\StubRenderer;
use App\Services\CrudGenerator\Writer;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Asks Gemini for records matching a module's columns and active locales,
 * downloads Pixabay photos for its image fields and writes a regular seeder
 * (class + JSON data + image assets) under database/seeders/Smart.
 */
final class SmartSeeder
{
    public const MAX_RECORDS = 20;

    public const DIRECTORY = 'database/seeders/Smart';

    public function __construct(
        private readonly Writer $writer,
        private readonly StubRenderer $renderer,
        private readonly SeedableModules $modules,
        private readonly GeminiClient $gemini,
        private readonly PixabayClient $pixabay,
    ) {}

    public static function make(): self
    {
        $writer = new Writer(config('crud-generator.base_path'));

        return new self(
            $writer,
            new StubRenderer(base_path('stubs/smart-seeder')),
            new SeedableModules(new Manifest($writer->path(config('crud-generator.manifest')))),
            app(GeminiClient::class),
            app(PixabayClient::class),
        );
    }

    public function modules(): SeedableModules
    {
        return $this->modules;
    }

    /**
     * @return array{class: string, records: int, warnings: string[], files: string[]}
     */
    public function generate(string $module, int $count, string $instruction): array
    {
        $entry = $this->modules->find($module) ?? throw new SmartSeederException("Module [{$module}] cannot be seeded.");
        $spec = $entry['spec'];
        $settings = AiSetting::current();
        $locales = active_langs();

        if (!$settings->hasGeminiKey()) {
            throw new SmartSeederException('Add a Gemini API key in the Smart Seeder settings first.');
        }

        if ($spec->imageFields() && !$settings->hasPixabayKey()) {
            throw new SmartSeederException('This module has image fields: add a Pixabay API key in the settings first.');
        }

        if ($locales->isEmpty()) {
            throw new SmartSeederException('There are no active languages.');
        }

        $count = max(1, min(self::MAX_RECORDS, $count));

        $data = $this->gemini->generateJson(
            $settings->gemini_api_key,
            $settings->gemini_model ?: AiSetting::DEFAULT_GEMINI_MODEL,
            $this->systemPrompt(),
            $this->prompt($spec, $locales, $count, $instruction),
            $this->schema($spec, $locales, $count),
        );

        $warnings = [];
        $records = $this->normalize($spec, $locales, (array) ($data['records'] ?? []), $count, $warnings);
        $class = $spec->name . 'SmartSeeder' . now()->format('YmdHis');

        $operations = [];
        $credits = [];
        $usedPhotos = [];

        foreach ($records as $index => &$record) {
            foreach ($spec->imageFields() as $field) {
                $query = $record['image_queries'][$field->name] ?? '';
                $photo = $this->pixabay->photo($settings->pixabay_api_key, $query, $usedPhotos);

                if (!$photo) {
                    $warnings[] = "No photo found for \"{$query}\" (record " . ($index + 1) . ').';

                    continue;
                }

                $usedPhotos[] = $photo['id'];
                $path = "assets/{$class}/" . ($index + 1) . "_{$field->name}.{$photo['extension']}";
                $operations[] = FileOperation::create(self::DIRECTORY . '/' . $path, $photo['contents']);
                $record['images'][$field->name] = [$path];
                $credits[] = Arr::only($photo, ['photographer', 'photographer_url', 'url']) + ['file' => $path, 'source' => 'Pixabay'];
            }

            unset($record['image_queries']);
        }
        unset($record);

        $payload = [
            'meta' => [
                'module' => $spec->name,
                'model' => $entry['model'],
                'morph_alias' => $spec->morphAlias(),
                'title_field' => $spec->titleField()?->name,
                'has_slug' => $spec->hasSlug,
                'default_locale' => $locales->first()->code,
                'collections' => collect($spec->imageFields())->mapWithKeys(fn (FieldSpec $field) => [$field->name => $spec->imageCollection($field)])->all(),
                'instruction' => $instruction,
                'count' => count($records),
                'ai_model' => $settings->gemini_model,
                'generated_at' => now()->toIso8601String(),
            ],
            'records' => $records,
            'credits' => $credits,
        ];

        array_unshift(
            $operations,
            FileOperation::create(self::DIRECTORY . "/{$class}.php", $this->renderer->render('seeder', ['class' => $class])),
            FileOperation::create(self::DIRECTORY . "/data/{$class}.json", json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"),
        );

        $result = $this->writer->write($operations);

        return ['class' => $class, 'records' => count($records), 'warnings' => $warnings, 'files' => $result['created']];
    }

    /**
     * @return array<int, array{meta: array, path: string}>
     */
    public function seeders(): array
    {
        $files = glob($this->writer->path(self::DIRECTORY . '/data') . DIRECTORY_SEPARATOR . '*.json') ?: [];
        $seeders = [];

        foreach ($files as $file) {
            $data = json_decode((string) file_get_contents($file), true);

            if (is_array($data['meta'] ?? null)) {
                $seeders[] = ['class' => basename($file, '.json'), 'meta' => $data['meta']];
            }
        }

        return array_reverse($seeders);
    }

    public function assetPath(string $class, string $file): string
    {
        return $this->writer->path(self::DIRECTORY . "/assets/{$class}/{$file}");
    }

    public function find(string $class): ?array
    {
        if (!preg_match('/^[A-Za-z0-9]+$/', $class)) {
            return null;
        }

        $data = json_decode((string) $this->writer->read(self::DIRECTORY . "/data/{$class}.json"), true);

        return is_array($data['meta'] ?? null) ? $data + ['class' => $class] : null;
    }

    public function schema(ModuleSpec $spec, Collection $locales, int $count): array
    {
        $string = fn (string $description) => ['type' => 'string', 'description' => $description];
        $properties = [];

        if ($spec->hasSlug) {
            $properties['slug'] = $string('URL slug: lowercase latin letters, digits and hyphens, based on the English title.');
        }

        if ($spec->hasPublishDate) {
            $properties['publish_date'] = $string('Publish date in YYYY-MM-DD format within the last year.');
        }

        if ($spec->hasOrder) {
            $properties['order'] = ['type' => 'integer', 'description' => 'Sort order starting at 1.'];
        }

        if ($spec->hasStatus) {
            $properties['status'] = ['type' => 'boolean', 'description' => 'Active flag, usually true.'];
        }

        foreach ($spec->mainFields() as $field) {
            $properties[$field->name] = $string($this->describe($field));
        }

        $translationFields = [];

        foreach ($spec->translatableFields() as $field) {
            $translationFields[$field->name] = $string($this->describe($field));
        }

        $properties['translations'] = [
            'type' => 'object',
            'properties' => $locales->mapWithKeys(fn ($lang) => [$lang->code => [
                'type' => 'object',
                'description' => "Content written natively in {$lang->name} ({$lang->code}).",
                'properties' => $translationFields,
                'required' => array_keys($translationFields),
            ]])->all(),
            'required' => $locales->pluck('code')->all(),
        ];

        if ($spec->imageFields()) {
            $queries = collect($spec->imageFields())->mapWithKeys(fn (FieldSpec $field) => [
                $field->name => $string("2-5 English words to search a real stock photo for \"{$field->label}\"."),
            ])->all();

            $properties['image_queries'] = ['type' => 'object', 'properties' => $queries, 'required' => array_keys($queries)];
        }

        return [
            'type' => 'object',
            'properties' => [
                'records' => [
                    'type' => 'array',
                    'minItems' => $count,
                    'maxItems' => $count,
                    'items' => ['type' => 'object', 'properties' => $properties, 'required' => array_keys($properties)],
                ],
            ],
            'required' => ['records'],
        ];
    }

    public function prompt(ModuleSpec $spec, Collection $locales, int $count, string $instruction): string
    {
        $languages = $locales->map(fn ($lang) => "{$lang->name} ({$lang->code})")->implode(', ');

        return implode("\n", [
            "Module: {$spec->label} ({$spec->name}).",
            "Generate exactly {$count} records.",
            "Languages: {$languages}. Every record must contain the same content in each language, written naturally by a native speaker, not word-for-word.",
            'Task from the developer:',
            $instruction,
        ]);
    }

    private function systemPrompt(): string
    {
        return implode("\n", [
            'You create realistic seed data for an admin panel content module.',
            'Follow the JSON schema exactly. Field descriptions give the meaning, type and length limits of each column.',
            'Make records distinct from each other, specific and believable; avoid placeholders such as "Lorem ipsum" or "Example".',
            'For rich text fields use only simple HTML: <p>, <h2>, <h3>, <strong>, <em>, <ul>, <ol>, <li>. Plain text fields must not contain HTML.',
            'Image queries describe a real photograph that fits the record, in English.',
        ]);
    }

    private function describe(FieldSpec $field): string
    {
        $parts = [$field->label . '.'];

        if ($field->isString()) {
            $parts[] = "Plain text, at most {$field->maxLength} characters.";
        } elseif ($field->quill) {
            $parts[] = 'Rich text as simple HTML, 2-4 short paragraphs or a list.';
        } else {
            $parts[] = 'Plain text, 1-3 sentences, no HTML.';
        }

        $parts[] = $field->required ? 'Required.' : 'Optional but fill it.';

        return implode(' ', $parts);
    }

    /**
     * Validates the AI response and coerces it into the shape the runner seeds.
     */
    public function normalize(ModuleSpec $spec, Collection $locales, array $raw, int $count, array &$warnings): array
    {
        $records = [];
        $errors = [];
        $slugs = [];

        foreach (array_slice(array_values($raw), 0, $count) as $index => $item) {
            $number = $index + 1;
            $item = is_array($item) ? $item : [];
            $main = [];
            $translations = [];

            foreach ($spec->mainFields() as $field) {
                $main[$field->name] = $this->clean($field, $item[$field->name] ?? '');

                if ($field->required && $main[$field->name] === '') {
                    $errors[] = "Record {$number}: [{$field->name}] is empty.";
                }
            }

            foreach ($locales as $lang) {
                $values = is_array($item['translations'][$lang->code] ?? null) ? $item['translations'][$lang->code] : null;

                if ($values === null) {
                    $errors[] = "Record {$number}: language [{$lang->code}] is missing.";

                    continue;
                }

                foreach ($spec->translatableFields() as $field) {
                    $translations[$lang->code][$field->name] = $this->clean($field, $values[$field->name] ?? '');

                    if ($field->required && $translations[$lang->code][$field->name] === '') {
                        $errors[] = "Record {$number}: [{$field->name}:{$lang->code}] is empty.";
                    }
                }
            }

            if ($spec->hasSlug) {
                $title = $spec->titleField()?->name;
                $source = $item['slug'] ?? ($translations['en'][$title] ?? reset($translations)[$title] ?? '');
                $main = ['slug' => $this->uniqueSlug($spec, (string) $source, $slugs)] + $main;
            }

            if ($spec->hasPublishDate) {
                $main['publish_date'] = $this->date($item['publish_date'] ?? null);
            }

            if ($spec->hasOrder) {
                $main['order'] = max(1, (int) ($item['order'] ?? $number));
            }

            if ($spec->hasStatus) {
                $main['status'] = (bool) ($item['status'] ?? true);
            }

            $records[] = [
                'main' => $main,
                'translations' => $translations,
                'images' => [],
                'image_queries' => array_map(
                    fn ($query) => Str::limit(trim(strip_tags((string) $query)), 100, ''),
                    is_array($item['image_queries'] ?? null) ? $item['image_queries'] : []
                ),
            ];
        }

        if ($errors) {
            throw new SmartSeederException('The AI response is incomplete: ' . implode(' ', array_slice($errors, 0, 5)));
        }

        if (!$records) {
            throw new SmartSeederException('The AI returned no records.');
        }

        if (count($records) < $count) {
            $warnings[] = 'The AI returned ' . count($records) . " of {$count} records.";
        }

        return $records;
    }

    private function clean(FieldSpec $field, mixed $value): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        if ($field->quill) {
            return trim((string) sanitize_html($value));
        }

        $value = trim(strip_tags($value));

        return $field->isString() ? Str::limit($value, $field->maxLength, '') : $value;
    }

    private function uniqueSlug(ModuleSpec $spec, string $source, array &$used): string
    {
        $base = Str::limit(Str::slug($source), 240, '') ?: Str::lower(Str::random(8));
        $slug = $base;

        for ($suffix = 2; in_array($slug, $used, true) || $this->slugTaken($spec, $slug); $suffix++) {
            $slug = "{$base}-{$suffix}";
        }

        $used[] = $slug;

        return $slug;
    }

    private function slugTaken(ModuleSpec $spec, string $slug): bool
    {
        try {
            return DB::table($spec->table())->where('slug', $slug)->exists();
        } catch (Throwable) {
            return false;
        }
    }

    private function date(mixed $value): string
    {
        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (Throwable) {
            return now()->format('Y-m-d');
        }
    }
}
