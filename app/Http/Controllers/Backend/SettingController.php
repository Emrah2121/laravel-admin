<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\StoreSettingRequest;
use App\Http\Requests\Backend\UpdateSettingRequest;
use App\Models\Setting;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SettingController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('settings index'), Response::HTTP_FORBIDDEN);

        if (request()->ajax()) {
            $query = Setting::with('translation')->latest();

            if (request()->filled('search')) {
                $search = request('search');

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhereHas('translations', function ($t) use ($search) {
                            $t->where('content', 'like', "%{$search}%");
                        });
                });
            }

            return $this->dataTable($query);
        }

        return view('backend.settings.index');
    }

    public function create()
    {
        abort_if(Gate::denies('settings create'), Response::HTTP_FORBIDDEN);

        $edit = false;
        $langs = active_langs();

        return view('backend.settings.form', compact('edit', 'langs'));
    }

    public function store(StoreSettingRequest $request)
    {
        abort_if(Gate::denies('settings create'), Response::HTTP_FORBIDDEN);

        $data = $request->validated();

        DB::beginTransaction();

        try {
            $setting = Setting::create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? null,
            ]);

            foreach (active_langs() as $lang) {
                $setting->translations()->create([
                    'locale' => $lang->code,
                    'content' => $data['content:' . $lang->code] ?? '',
                ]);
            }

            Cache::forget('settings');
            DB::commit();

            return redirect(route('backend.settings.index'))->withSuccess(trans('backend.messages.success.create'));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return redirect()->back()->withError(trans('backend.messages.error.create'));
        }
    }

    public function show(Setting $setting)
    {
        abort_if(Gate::denies('settings show'), Response::HTTP_FORBIDDEN);

        $langs = active_langs();
        $translations = $langs->mapWithKeys(fn ($lang) => [$lang->code => $setting->translate($lang->code)]);

        return view('backend.settings.show', compact('setting', 'langs', 'translations'));
    }

    public function edit(Setting $setting)
    {
        abort_if(Gate::denies('settings edit'), Response::HTTP_FORBIDDEN);

        $edit = true;
        $langs = active_langs();

        return view('backend.settings.form', compact('setting', 'edit', 'langs'));
    }

    public function update(UpdateSettingRequest $request, Setting $setting)
    {
        abort_if(Gate::denies('settings edit'), Response::HTTP_FORBIDDEN);

        $data = $request->validated();

        DB::beginTransaction();

        try {
            $setting->update([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? null,
            ]);

            foreach (active_langs() as $lang) {
                $setting->translations()->updateOrCreate(
                    ['locale' => $lang->code],
                    ['content' => $data['content:' . $lang->code] ?? '']
                );
            }

            Cache::forget('settings');
            DB::commit();

            return redirect(route('backend.settings.index'))->withSuccess(trans('backend.messages.success.update'));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return redirect()->back()->withError(trans('backend.messages.error.update'));
        }
    }

    public function destroy(Setting $setting)
    {
        abort_if(Gate::denies('settings delete'), Response::HTTP_FORBIDDEN);

        try {
            $setting->delete();
            Cache::forget('settings');

            return response(['status' => 1]);
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return response(['status' => 0]);
        }
    }

    protected function dataTable($data)
    {
        return datatables()
            ->of($data)
            ->addColumn('checkbox', fn ($row) => '<div class="form-check form-check-sm form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" value="' . $row->id . '" />
                        </div>')
            ->addColumn('name', fn ($row) => '<a href="' . route('backend.settings.edit', $row->id) . '" class="text-gray-800 text-hover-primary fw-bold">' . $row->name . '</a>')
            ->addColumn('content', fn ($row) => Str::limit($row->translation?->content, 30))
            ->addColumn('actions', fn ($row) => $this->permissions($row->id))
            ->rawColumns(['checkbox', 'name', 'content', 'actions'])
            ->make(true);
    }

    protected function permissions($id)
    {
        $result = '<a href="#"
        class="btn btn-light btn-active-light-primary btn-flex btn-center btn-sm"
        data-kt-menu-trigger="click"
        data-kt-menu-placement="bottom-end">'
            . trans('backend.labels.actions') .
            '<i class="ki-outline ki-down fs-5 ms-1"></i>
        </a>';

        $result .= '<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">';

        if (admin()->can('settings show')) {
            $result .= '<div class="menu-item px-3">
                            <a href="' . route('backend.settings.show', ['setting' => $id]) . '" class="menu-link px-3">' . trans('backend.buttons.view') . '</a>
                        </div>';
        }

        if (admin()->can('settings edit')) {
            $result .= '<div class="menu-item px-3">
                            <a href="' . route('backend.settings.edit', ['setting' => $id]) . '" class="menu-link px-3">' . trans('backend.buttons.edit') . '</a>
                        </div>';
        }

        if (admin()->can('settings delete')) {
            $result .= '<div class="menu-item px-3">
                            <a href="' . route('backend.settings.destroy', ['setting' => $id]) . '" class="menu-link px-3 btn-delete">' . trans('backend.buttons.delete') . '</a>
                        </div>';
        }

        $result .= '</div>';

        return $result;
    }
}
