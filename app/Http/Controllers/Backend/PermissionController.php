<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\StorePermissionRequest;
use App\Http\Requests\Backend\UpdatePermissionRequest;
use Exception;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\Response;

class PermissionController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('permissions index'), Response::HTTP_FORBIDDEN);

        if (request()->ajax()) {
            $query = Permission::query()->latest();

            if (request()->filled('search')) {
                $search = request('search');
                $query->where('name', 'like', "%$search%");
            }

            return $this->dataTable($query);
        }

        return view('backend.permissions.index');
    }

    public function create()
    {
        abort_if(Gate::denies('permissions create'), Response::HTTP_FORBIDDEN);

        $edit = false;

        return view('backend.permissions.form', compact('edit'));
    }

    public function store(StorePermissionRequest $request)
    {
        abort_if(Gate::denies('permissions create'), Response::HTTP_FORBIDDEN);

        Permission::create($request->validated());

        return redirect(route('backend.permissions.index'))->withSuccess(trans('backend.messages.success.create'));
    }

    public function show(Permission $permission)
    {
        abort_if(Gate::denies('permissions show'), Response::HTTP_FORBIDDEN);

        return view('backend.permissions.show', compact('permission'));
    }

    public function edit(Permission $permission)
    {
        abort_if(Gate::denies('permissions edit'), Response::HTTP_FORBIDDEN);

        $edit = true;

        return view('backend.permissions.form', compact('permission', 'edit'));
    }

    public function update(UpdatePermissionRequest $request, Permission $permission)
    {
        abort_if(Gate::denies('permissions edit'), Response::HTTP_FORBIDDEN);

        $permission->update($request->validated());

        return redirect(route('backend.permissions.index'))->withSuccess(trans('backend.messages.success.update'));
    }

    public function destroy(Permission $permission)
    {
        abort_if(Gate::denies('permissions delete'), Response::HTTP_FORBIDDEN);

        try {
            $permission->delete();

            return response(['status' => 1]);
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return response(['status' => 0]);
        }
    }

    protected function dataTable($query)
    {
        return datatables()
            ->of($query)
            ->addColumn('checkbox', fn ($row) => '<div class="form-check form-check-sm form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" value="' . $row->id . '" />
                        </div>')
            ->addColumn('assigned_to', function ($row) {
                $html = '';
                foreach ($row->roles as $role) {
                    $colors = ['primary', 'danger', 'success', 'info', 'warning'];
                    $color = $colors[$role->id % count($colors)];
                    $html .= '<a href="' . route('backend.roles.show', $role->id) . '" class="badge badge-light-' . $color . ' fs-7 m-1">' . $role->name . '</a>';
                }

                return $html;
            })
            ->editColumn('created_at', fn ($row) => $row->created_at->format('d M Y, h:i a'))
            ->addColumn('actions', fn ($row) => $this->permissions($row->id))
            ->rawColumns(['checkbox', 'assigned_to', 'actions'])
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

        if (admin()->can('permissions show')) {
            $result .= '<div class="menu-item px-3">
                            <a href="' . route('backend.permissions.show', ['permission' => $id]) . '" class="menu-link px-3">' . trans('backend.buttons.view') . '</a>
                        </div>';
        }

        if (admin()->can('permissions edit')) {
            $result .= '<div class="menu-item px-3">
                            <a href="' . route('backend.permissions.edit', ['permission' => $id]) . '" class="menu-link px-3">' . trans('backend.buttons.edit') . '</a>
                        </div>';
        }

        if (admin()->can('permissions delete')) {
            $result .= '<div class="menu-item px-3">
                            <a href="' . route('backend.permissions.destroy', ['permission' => $id]) . '" class="menu-link px-3 btn-delete">' . trans('backend.buttons.delete') . '</a>
                        </div>';
        }

        $result .= '</div>';

        return $result;
    }
}
