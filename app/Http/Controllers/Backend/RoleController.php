<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\StoreRoleRequest;
use App\Http\Requests\Backend\UpdateRoleRequest;
use App\Models\Admin;
use Exception;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class RoleController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('roles index'), Response::HTTP_FORBIDDEN);

        if (request()->ajax()) {
            $query = Role::where('name', '!=', Admin::DEVELOPER_ROLE);

            if (request()->filled('search')) {
                $search = request('search');
                $query->where('name', 'like', "%$search%");
            }

            return $this->dataTable($query);
        }

        return view('backend.roles.index');
    }

    public function create()
    {
        abort_if(Gate::denies('roles create'), Response::HTTP_FORBIDDEN);

        $permissions = Permission::all();
        $edit = false;

        return view('backend.roles.form', compact('permissions', 'edit'));
    }

    public function store(StoreRoleRequest $request)
    {
        abort_if(Gate::denies('roles create'), Response::HTTP_FORBIDDEN);

        try {
            $role = Role::create(['name' => $request->validated('name'), 'guard_name' => 'admin']);
            $role->syncPermissions(array_map('intval', $request->validated('permissions')));

            return redirect(route('backend.roles.index'))->withSuccess(trans('backend.messages.success.create'));
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return redirect(route('backend.roles.index'))->withError(trans('backend.messages.error.create'));
        }
    }

    public function show(Role $role)
    {
        abort_if(Gate::denies('roles show'), Response::HTTP_FORBIDDEN);
        abort_if($role->name === Admin::DEVELOPER_ROLE, Response::HTTP_FORBIDDEN);

        return view('backend.roles.show', compact('role'));
    }

    public function edit(Role $role)
    {
        abort_if(Gate::denies('roles edit'), Response::HTTP_FORBIDDEN);
        abort_if($role->name === Admin::DEVELOPER_ROLE, Response::HTTP_FORBIDDEN);

        $permissions = Permission::all();
        $rolePermissions = $role->permissions()->pluck('id')->toArray();
        $edit = true;

        return view('backend.roles.form', compact('role', 'permissions', 'rolePermissions', 'edit'));
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        abort_if(Gate::denies('roles edit'), Response::HTTP_FORBIDDEN);
        abort_if($role->name === Admin::DEVELOPER_ROLE, Response::HTTP_FORBIDDEN);

        try {
            $role->update(['name' => $request->validated('name')]);
            $role->syncPermissions(array_map('intval', $request->validated('permissions')));

            return redirect(route('backend.roles.index'))->withSuccess(trans('backend.messages.success.update'));
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return redirect(route('backend.roles.index'))->withError(trans('backend.messages.error.update'));
        }
    }

    public function destroy(Role $role)
    {
        abort_if(Gate::denies('roles delete'), Response::HTTP_FORBIDDEN);
        abort_if($role->name === Admin::DEVELOPER_ROLE, Response::HTTP_FORBIDDEN);

        try {
            if (Admin::where('role_id', $role->id)->count()) {
                return response(['status' => 2]);
            }

            $role->delete();

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
            ->addColumn('name', fn ($row) => '<a href="' . route('backend.roles.edit', ['role' => $row->id]) . '" class="text-gray-800 text-hover-primary fs-5 fw-bold">' . e($row->name) . '</a>')
            ->editColumn('created_at', fn ($row) => $row->created_at->format('d M Y, h:i a'))
            ->addColumn('actions', fn ($row) => $this->permissions($row->id))
            ->rawColumns(['checkbox', 'name', 'actions'])
            ->make(true);
    }

    protected function permissions($id): string
    {
        $result = '<a href="#"
        class="btn btn-light btn-active-light-primary btn-flex btn-center btn-sm"
        data-kt-menu-trigger="click"
        data-kt-menu-placement="bottom-end">'
            . trans('backend.labels.actions') .
            '<i class="ki-outline ki-down fs-5 ms-1"></i>
        </a>';

        $result .= '<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">';

        if (admin()->can('roles show')) {
            $result .= '<div class="menu-item px-3">
                            <a href="' . route('backend.roles.show', ['role' => $id]) . '" class="menu-link px-3">' . trans('backend.buttons.view') . '</a>
                        </div>';
        }

        if (admin()->can('roles edit')) {
            $result .= '<div class="menu-item px-3">
                            <a href="' . route('backend.roles.edit', ['role' => $id]) . '" class="menu-link px-3">' . trans('backend.buttons.edit') . '</a>
                        </div>';
        }

        if (admin()->can('roles delete')) {
            $result .= '<div class="menu-item px-3">
                            <a href="' . route('backend.roles.destroy', ['role' => $id]) . '" class="menu-link px-3 btn-delete">' . trans('backend.buttons.delete') . '</a>
                        </div>';
        }

        $result .= '</div>';

        return $result;
    }
}
