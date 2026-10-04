<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\StoreLanguageRequest;
use App\Http\Requests\Backend\UpdateLanguageRequest;
use App\Models\Language;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LanguageController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('languages index'), Response::HTTP_FORBIDDEN);

        if (request()->ajax()) {
            $query = Language::query();

            if (request()->filled('search')) {
                $query->where('name', 'like', '%' . request('search') . '%');
            }

            return $this->dataTable($query);
        }

        return view('backend.languages.index');
    }

    public function create()
    {
        abort_if(Gate::denies('languages create'), Response::HTTP_FORBIDDEN);

        $edit = false;

        return view('backend.languages.form', compact('edit'));
    }

    public function store(StoreLanguageRequest $request)
    {
        abort_if(Gate::denies('languages create'), Response::HTTP_FORBIDDEN);

        Language::create($request->validated());
        Cache::forget('active_langs');

        return redirect(route('backend.languages.index'))->withSuccess(trans('backend.messages.success.create'));
    }

    public function edit(Language $language)
    {
        abort_if(Gate::denies('languages edit'), Response::HTTP_FORBIDDEN);

        $edit = true;

        return view('backend.languages.form', compact('language', 'edit'));
    }

    public function update(UpdateLanguageRequest $request, Language $language)
    {
        abort_if(Gate::denies('languages edit'), Response::HTTP_FORBIDDEN);

        $language->update($request->validated());
        Cache::forget('active_langs');

        return redirect(route('backend.languages.index'))->withSuccess(trans('backend.messages.success.update'));
    }

    public function destroy(Language $language)
    {
        abort_if(Gate::denies('languages delete'), Response::HTTP_FORBIDDEN);

        try {
            if ($language->default) {
                return response(['status' => 2]);
            }

            $language->delete();
            Cache::forget('active_langs');

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
            ->addColumn('default', fn ($row) => badge($row->default))
            ->addColumn('status', fn ($row) => badge($row->status))
            ->addColumn('actions', fn ($row) => $this->permissions($row->id))
            ->rawColumns(['default', 'status', 'actions'])
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

        if (admin()->can('languages edit')) {
            $result .= '<div class="menu-item px-3">
                            <a href="' . route('backend.languages.edit', ['language' => $id]) . '" class="menu-link px-3">' . trans('backend.buttons.edit') . '</a>
                        </div>';
        }

        if (admin()->can('languages delete')) {
            $result .= '<div class="menu-item px-3">
                            <a href="' . route('backend.languages.destroy', ['language' => $id]) . '" class="menu-link px-3 btn-delete">' . trans('backend.buttons.delete') . '</a>
                        </div>';
        }

        $result .= '</div>';

        return $result;
    }
}
