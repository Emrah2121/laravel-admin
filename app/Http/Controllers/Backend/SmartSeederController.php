<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\StoreSmartSeederRequest;
use App\Http\Requests\Backend\UpdateAiSettingRequest;
use App\Models\AiSetting;
use App\Services\CrudGenerator\CrudGeneratorException;
use App\Services\SmartSeeder\SmartSeeder;
use App\Services\SmartSeeder\SmartSeederException;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SmartSeederController extends Controller
{
    public function index()
    {
        $smartSeeder = SmartSeeder::make();
        $modules = collect($smartSeeder->modules()->all())->map(fn ($module) => $module['spec']->label)->all();
        $seeders = $smartSeeder->seeders();
        $settings = AiSetting::current();
        $maxRecords = SmartSeeder::MAX_RECORDS;

        return view('backend.smart_seeder.index', compact('modules', 'seeders', 'settings', 'maxRecords'));
    }

    public function settings(UpdateAiSettingRequest $request)
    {
        // Blank key fields keep the stored keys, which are never sent back to the browser.
        $data = array_filter($request->validated(), fn ($value) => filled($value));

        AiSetting::current()->update($data);

        return redirect(route('backend.smart_seeder.index'))
            ->withSuccess(trans('backend.messages.success.update'));
    }

    public function store(StoreSmartSeederRequest $request)
    {
        $data = $request->validated();

        // The AI call and photo downloads can take a minute or two.
        set_time_limit(300);

        try {
            $result = SmartSeeder::make()->generate($data['module'], (int) $data['count'], $data['instruction']);
        } catch (SmartSeederException|CrudGeneratorException $e) {
            return redirect()->back()->withInput()->withError($this->flashText($e->getMessage()));
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return redirect()->back()->withInput()->withError(trans('backend.messages.error.create'));
        }

        return redirect(route('backend.smart_seeder.show', ['seeder' => $result['class']]))
            ->with('smart_seeder_warnings', $result['warnings'])
            ->withSuccess(trans('backend.smart_seeder.generated', ['count' => $result['records']]));
    }

    public function show(string $seeder)
    {
        $data = SmartSeeder::make()->find($seeder);

        abort_if(!$data, 404);

        $langs = active_langs();
        $warnings = session('smart_seeder_warnings', []);
        $credits = collect($data['credits'] ?? [])->keyBy('file')->all();

        return view('backend.smart_seeder.show', compact('data', 'langs', 'warnings', 'credits'));
    }

    public function asset(string $seeder, string $file)
    {
        $smartSeeder = SmartSeeder::make();

        abort_if(!$smartSeeder->find($seeder) || !preg_match('/^\d+_[a-z0-9_]+\.(jpg|png)$/', $file), 404);

        $path = $smartSeeder->assetPath($seeder, $file);

        abort_if(!is_file($path), 404);

        return response()->file($path);
    }

    public function run(string $seeder)
    {
        abort_if(!SmartSeeder::make()->find($seeder), 404);

        try {
            Artisan::call('db:seed', ['--class' => "Database\\Seeders\\Smart\\{$seeder}", '--force' => true]);
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return redirect()->back()->withError($this->flashText($e->getMessage()));
        }

        return redirect()->back()->withSuccess($this->flashText(Artisan::output()) ?: trans('backend.messages.success.create'));
    }

    /**
     * Flash messages are printed into a JavaScript string by the notify() helper,
     * so text coming from APIs or Artisan must not contain quotes or line breaks.
     */
    private function flashText(string $text): string
    {
        return Str::limit(trim(preg_replace('/[\'"\\\\<>\r\n]+/', ' ', strip_tags($text))), 300);
    }
}
