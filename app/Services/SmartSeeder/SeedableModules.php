<?php

namespace App\Services\SmartSeeder;

use App\Models\Blog;
use App\Services\CrudGenerator\Manifest;
use App\Services\CrudGenerator\ModuleSpec;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Modules whose columns are known: Blog plus every module recorded in the
 * CRUD generator manifest whose model and tables exist.
 */
final class SeedableModules
{
    public function __construct(private readonly Manifest $manifest) {}

    /**
     * @return array<string, array{spec: ModuleSpec, model: class-string<Model>}>
     */
    public function all(): array
    {
        $modules = ['Blog' => ['spec' => $this->blogSpec(), 'model' => Blog::class]];

        foreach ($this->manifest->all() as $entry) {
            $spec = ModuleSpec::fromArray($entry['spec'] ?? []);
            $model = 'App\\Models\\' . $spec->name;

            if (class_exists($model) && is_subclass_of($model, Model::class) && $this->tablesExist($spec)) {
                $modules[$spec->name] = ['spec' => $spec, 'model' => $model];
            }
        }

        return $modules;
    }

    /**
     * @return array{spec: ModuleSpec, model: class-string<Model>}|null
     */
    public function find(string $name): ?array
    {
        return $this->all()[$name] ?? null;
    }

    /**
     * Mirrors the Blog module (see docs/crud-generator-conventions.md).
     */
    private function blogSpec(): ModuleSpec
    {
        return ModuleSpec::fromArray([
            'name' => 'Blog',
            'label' => trans('backend.titles.blogs'),
            'fields' => [
                ['name' => 'title', 'label' => 'Title', 'type' => 'string', 'required' => true, 'translatable' => true],
                ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'string', 'required' => false, 'translatable' => true],
                ['name' => 'content', 'label' => 'Content', 'type' => 'textarea', 'required' => true, 'translatable' => true, 'quill' => true],
                ['name' => 'images', 'label' => 'Images', 'type' => 'image'],
            ],
        ]);
    }

    private function tablesExist(ModuleSpec $spec): bool
    {
        try {
            return Schema::hasTable($spec->table()) && Schema::hasTable($spec->translationTable());
        } catch (\Throwable) {
            return false;
        }
    }
}
