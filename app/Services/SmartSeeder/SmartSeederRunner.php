<?php

namespace App\Services\SmartSeeder;

use App\Models\Document;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Seeds the records of a Smart Seeder JSON file. Records that already exist
 * (same slug, or same title in the default locale) are skipped, so running a
 * seeder twice does not create duplicates.
 */
class SmartSeederRunner
{
    /**
     * @return array{created: int, skipped: int}
     */
    public function run(string $jsonPath): array
    {
        $data = json_decode((string) @file_get_contents($jsonPath), true);
        $meta = $data['meta'] ?? null;
        $model = $meta['model'] ?? null;

        if (!is_array($meta) || !is_string($model) || !str_starts_with($model, 'App\\Models\\') || !is_subclass_of($model, Model::class)) {
            throw new SmartSeederException("Invalid Smart Seeder data file [{$jsonPath}].");
        }

        $baseDirectory = dirname($jsonPath, 2);
        $result = ['created' => 0, 'skipped' => 0];
        $storedFiles = [];

        try {
            DB::transaction(function () use ($data, $meta, $model, $baseDirectory, &$result, &$storedFiles) {
                foreach ($data['records'] ?? [] as $record) {
                    if ($this->exists($model, $meta, $record)) {
                        $result['skipped']++;

                        continue;
                    }

                    $item = $model::create($record['main'] ?? []);

                    foreach ($record['translations'] ?? [] as $locale => $fields) {
                        $item->translations()->create(['locale' => $locale] + $fields);
                    }

                    foreach ($record['images'] ?? [] as $field => $paths) {
                        $collection = $meta['collections'][$field] ?? null;

                        foreach ((array) $paths as $path) {
                            $file = $baseDirectory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);

                            if (!$collection || str_contains($path, '..') || !is_file($file)) {
                                continue;
                            }

                            $stored = Storage::disk('documents')->putFile($collection, new File($file));
                            $storedFiles[] = $stored;

                            Document::create([
                                'manipulationable_type' => $meta['morph_alias'],
                                'manipulationable_id' => $item->id,
                                'document' => $stored,
                                'collection_name' => $collection,
                            ]);
                        }
                    }

                    $result['created']++;
                }
            });
        } catch (Throwable $e) {
            Storage::disk('documents')->delete($storedFiles);

            throw $e;
        }

        return $result;
    }

    private function exists(string $model, array $meta, array $record): bool
    {
        if (!empty($meta['has_slug']) && isset($record['main']['slug'])) {
            return $model::where('slug', $record['main']['slug'])->exists();
        }

        $locale = $meta['default_locale'] ?? null;
        $field = $meta['title_field'] ?? null;
        $title = $record['translations'][$locale][$field] ?? null;

        if (!$field || $title === null) {
            return false;
        }

        return $model::whereHas('translations', fn ($query) => $query->where('locale', $locale)->where($field, $title))->exists();
    }
}
