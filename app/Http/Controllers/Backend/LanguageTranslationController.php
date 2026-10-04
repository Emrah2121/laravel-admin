<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\StoreTranslationRequest;
use App\Http\Requests\Backend\UpdateTranslationRequest;
use App\Models\Translation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LanguageTranslationController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('translations index'), Response::HTTP_FORBIDDEN);

        $languages = active_langs();

        if ($request->ajax()) {
            $query = Translation::query();

            if ($request->filled('group') && $request->group !== 'all') {
                $query->where('group', $request->group);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('key', 'like', "%{$search}%")
                        ->orWhere('group', 'like', "%{$search}%")
                        ->orWhere('value', 'like', "%{$search}%");
                });
            }

            return $this->dataTable($query, $languages);
        }

        $groups = Translation::select('group')
            ->distinct()
            ->orderBy('group')
            ->pluck('group');

        return view('backend.translations.index', compact('languages', 'groups'));
    }

    public function create()
    {
        abort_if(Gate::denies('translations create'), Response::HTTP_FORBIDDEN);

        $languages = active_langs();
        $groups = Translation::select('group')->distinct()->orderBy('group')->pluck('group');
        $edit = false;

        return view('backend.translations.form', compact('languages', 'groups', 'edit'));
    }

    public function store(StoreTranslationRequest $request)
    {
        abort_if(Gate::denies('translations create'), Response::HTTP_FORBIDDEN);

        $data = $request->validated();

        Translation::updateOrCreate(
            ['group' => $data['group'], 'key' => $data['key']],
            ['value' => $data['value'] ?? []]
        );

        return redirect()->route('backend.translations.index')
            ->withSuccess(trans('backend.messages.success.create'));
    }

    public function edit(Translation $translation)
    {
        abort_if(Gate::denies('translations edit'), Response::HTTP_FORBIDDEN);

        $languages = active_langs();
        $groups = Translation::select('group')->distinct()->orderBy('group')->pluck('group');
        $edit = true;

        return view('backend.translations.form', compact('translation', 'languages', 'groups', 'edit'));
    }

    public function update(UpdateTranslationRequest $request, Translation $translation)
    {
        abort_if(Gate::denies('translations edit'), Response::HTTP_FORBIDDEN);

        $data = $request->validated();

        $translation->update([
            'group' => $data['group'],
            'key' => $data['key'],
            'value' => $data['value'] ?? [],
        ]);

        return redirect()->route('backend.translations.index')
            ->withSuccess(trans('backend.messages.success.update'));
    }

    public function destroy(Translation $translation)
    {
        abort_if(Gate::denies('translations delete'), Response::HTTP_FORBIDDEN);

        try {
            $translation->delete();

            return response(['status' => 1]);
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return response(['status' => 0]);
        }
    }

    public function import()
    {
        abort_if(Gate::denies('translations create'), Response::HTTP_FORBIDDEN);

        try {
            Artisan::call('translations:import');

            return redirect()->route('backend.translations.index')
                ->withSuccess(trans('backend.messages.success.import'));
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return redirect()->route('backend.translations.index')
                ->withError(trans('backend.messages.error.import'));
        }
    }

    public function clearCache()
    {
        abort_if(Gate::denies('translations edit'), Response::HTTP_FORBIDDEN);

        Translation::clearTranslationCache();

        return redirect()->route('backend.translations.index')
            ->withSuccess(trans('backend.messages.success.clear_cache'));
    }

    protected function dataTable($data, $languages)
    {
        return datatables()
            ->of($data)
            ->addColumn('group_badge', function ($row) {
                $group = e($row->group);
                $badgeClass = $group === '*' ? 'badge-light-warning' : 'badge-light-primary';

                return "<span class=\"badge {$badgeClass} fs-7 fw-bold\">{$group}</span>";
            })
            ->addColumn('key_formatted', function ($row) {
                $key = e($row->key);

                return "<div class=\"text-gray-800 fw-bold text-hover-primary mb-1 text-break\">{$key}</div>";
            })
            ->addColumn('translations_preview', function ($row) use ($languages) {
                $html = '<div class="d-flex flex-column gap-1">';
                $values = $row->value ?? [];

                foreach ($languages as $lang) {
                    $val = $values[$lang->code] ?? '';
                    $valTrimmed = mb_strlen($val) > 80 ? mb_substr($val, 0, 80) . '...' : $val;
                    $badgeStyle = empty($val) ? 'badge-light-danger' : 'badge-light-success';

                    $html .= '<div class="d-flex align-items-center fs-8">';
                    $html .= "<span class=\"badge {$badgeStyle} me-2 text-uppercase\" style=\"min-width: 32px;\">{$lang->code}</span>";
                    $html .= '<span class="text-gray-600 text-break">' . (empty($val) ? '<em class="text-muted fs-9">' . trans('backend.messages.error.not_found') . '</em>' : e($valTrimmed)) . '</span>';
                    $html .= '</div>';
                }

                $html .= '</div>';

                return $html;
            })
            ->addColumn('actions', fn ($row) => $this->permissions($row->id))
            ->rawColumns(['group_badge', 'key_formatted', 'translations_preview', 'actions'])
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

        if (admin()->can('translations edit')) {
            $result .= '<div class="menu-item px-3">
                            <a href="' . route('backend.translations.edit', ['translation' => $id]) . '" class="menu-link px-3">' . trans('backend.buttons.edit') . '</a>
                        </div>';
        }

        if (admin()->can('translations delete')) {
            $result .= '<div class="menu-item px-3">
                            <a href="' . route('backend.translations.destroy', ['translation' => $id]) . '" class="menu-link px-3 btn-delete">' . trans('backend.buttons.delete') . '</a>
                        </div>';
        }

        $result .= '</div>';

        return $result;
    }
}
