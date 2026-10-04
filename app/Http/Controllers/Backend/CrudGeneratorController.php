<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\PreviewCrudModuleRequest;
use App\Http\Requests\Backend\StoreCrudModuleRequest;
use App\Services\CrudGenerator\CrudGenerator;
use App\Services\CrudGenerator\CrudGeneratorException;
use App\Services\CrudGenerator\FieldSpec;
use App\Services\CrudGenerator\FileOperation;
use App\Services\CrudGenerator\ModuleSpec;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class CrudGeneratorController extends Controller
{
    private const SESSION_KEY = 'crud_generator.specs';

    public function index()
    {
        $modules = array_reverse(CrudGenerator::make()->manifest()->all());

        return view('backend.crud_generator.index', compact('modules'));
    }

    public function create()
    {
        $fields = array_values(old('fields', [[
            'name' => 'title',
            'label' => 'Title',
            'type' => FieldSpec::TYPE_STRING,
            'required' => 1,
            'translatable' => 1,
            'quill' => 0,
            'max_length' => FieldSpec::DEFAULT_MAX_LENGTH,
        ]]));
        $types = FieldSpec::TYPES;

        return view('backend.crud_generator.form', compact('fields', 'types'));
    }

    public function preview(PreviewCrudModuleRequest $request)
    {
        $spec = $request->spec();
        $generator = CrudGenerator::make();
        $plan = $generator->plan($spec);

        if ($plan['errors'] || $plan['conflicts']) {
            return redirect()->back()->withInput()->withErrors($plan['errors'] ?: ['name' => $plan['conflicts']]);
        }

        $token = Str::random(40);
        session()->put(self::SESSION_KEY . '.' . $token, $spec->toArray());

        $created = [];
        $modified = [];

        foreach ($plan['preview'] as $item) {
            $operation = $item['operation'];

            if ($operation->type === FileOperation::CREATE) {
                $created[] = $operation->path;
            } elseif ($item['changed']) {
                $modified[$operation->path][] = $item['diff'];
            }
        }

        $names = [
            'Model' => "App\\Models\\{$spec->name}, App\\Models\\{$spec->translationModel()}",
            'Controller' => "App\\Http\\Controllers\\Backend\\{$spec->controller()}",
            'Requests' => "{$spec->storeRequest()}, {$spec->updateRequest()}",
            'Tables' => "{$spec->table()}, {$spec->translationTable()}",
            'Route' => "/gload/{$spec->routePath()} (backend.{$spec->key()}.*)",
            'Permissions' => implode(', ', array_map(fn ($action) => $spec->permission($action), ['index', 'create', 'show', 'edit', 'delete'])),
            'Sidebar' => "{$spec->label} (ki-{$spec->icon})",
        ];
        $artisanAllowed = $generator->artisanAllowed();

        return view('backend.crud_generator.preview', compact('spec', 'token', 'created', 'modified', 'names', 'artisanAllowed'));
    }

    public function store(StoreCrudModuleRequest $request)
    {
        $data = $request->validated();
        $spec = session()->pull(self::SESSION_KEY . '.' . $data['token']);

        if (!$spec) {
            return redirect(route('backend.crud_generator.create'))
                ->withError(trans('backend.crud_generator.expired'));
        }

        $spec = ModuleSpec::fromArray($spec);

        try {
            $result = CrudGenerator::make()->generate($spec, (bool) ($data['migrate'] ?? false), (bool) ($data['seed'] ?? false));
        } catch (CrudGeneratorException $e) {
            return redirect(route('backend.crud_generator.create'))
                ->withInput($spec->toArray())
                ->withErrors($e->errors ?: ['name' => [$e->getMessage()]]);
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return redirect(route('backend.crud_generator.create'))
                ->withInput($spec->toArray())
                ->withError(trans('backend.messages.error.create'));
        }

        return redirect(route('backend.crud_generator.show', ['module' => $spec->name]))
            ->with('crud_generator_result', $result)
            ->withSuccess(trans('backend.messages.success.create'));
    }

    public function show(string $module)
    {
        $entry = CrudGenerator::make()->manifest()->find($module);

        abort_if(!$entry, 404);

        $spec = ModuleSpec::fromArray($entry['spec']);
        $result = session('crud_generator_result');
        $migrated = $result['artisan']['migrate']['successful'] ?? false;
        $seeded = $result['artisan']['seed']['successful'] ?? false;
        $moduleUrl = Route::has("backend.{$spec->key()}.index") ? route("backend.{$spec->key()}.index") : null;

        return view('backend.crud_generator.show', compact('entry', 'spec', 'result', 'migrated', 'seeded', 'moduleUrl'));
    }
}
