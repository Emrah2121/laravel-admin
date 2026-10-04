<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\StoreAdminRequest;
use App\Http\Requests\Backend\UpdateAdminRequest;
use App\Models\Admin;
use App\Services\UploadDocumentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AdminController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('admins index'), Response::HTTP_FORBIDDEN);

        if (request()->ajax()) {
            $query = Admin::whereHas('role', fn ($q) => $q->where('name', '!=', Admin::DEVELOPER_ROLE));

            if (request()->filled('search')) {
                $search = request('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%");
                });
            }

            return $this->dataTable($query);
        }

        $roles = Role::where('name', '!=', Admin::DEVELOPER_ROLE)->get();

        return view('backend.admins.index', compact('roles'));
    }

    public function create()
    {
        abort_if(Gate::denies('admins create'), Response::HTTP_FORBIDDEN);

        $roles = Role::where('name', '!=', Admin::DEVELOPER_ROLE)->get();
        $edit = false;

        return view('backend.admins.form', compact('roles', 'edit'));
    }

    public function store(StoreAdminRequest $request, UploadDocumentService $uploadDocumentService)
    {
        abort_if(Gate::denies('admins create'), Response::HTTP_FORBIDDEN);

        $role = Role::findById($request->validated('role_id'), 'admin');
        abort_if($role->name === Admin::DEVELOPER_ROLE, Response::HTTP_FORBIDDEN);

        try {
            DB::transaction(function () use ($request, $role, $uploadDocumentService) {
                $admin = Admin::create($request->validated());
                $admin->assignRole($role);

                if ($request->hasFile('image')) {
                    $uploadDocumentService->upload('admin', 'image', $admin->id, 'avatar', false, $request);
                }
            });

            return redirect(route('backend.admins.index'))->withSuccess(trans('backend.messages.success.create'));
        } catch (Throwable $e) {
            if ($e instanceof ValidationException) {
                return redirect()->back()->withErrors($e->errors())->withInput();
            }

            Log::error($e->getMessage());

            return redirect(route('backend.admins.index'))->withError(trans('backend.messages.error.create'));
        }
    }

    public function edit(Admin $admin)
    {
        abort_if(Gate::denies('admins edit'), Response::HTTP_FORBIDDEN);
        abort_if($admin->isDeveloper(), Response::HTTP_FORBIDDEN);

        $roles = Role::where('name', '!=', Admin::DEVELOPER_ROLE)->get();
        $edit = true;

        return view('backend.admins.form', compact('admin', 'roles', 'edit'));
    }

    public function update(UpdateAdminRequest $request, Admin $admin, UploadDocumentService $uploadDocumentService)
    {
        abort_if(Gate::denies('admins edit'), Response::HTTP_FORBIDDEN);
        abort_if($admin->isDeveloper(), Response::HTTP_FORBIDDEN);

        $role = Role::findById($request->validated('role_id'), 'admin');
        abort_if($role->name === Admin::DEVELOPER_ROLE, Response::HTTP_FORBIDDEN);

        try {
            DB::transaction(function () use ($request, $admin, $role, $uploadDocumentService) {
                $data = $request->validated();

                $admin->syncRoles($role);

                if (empty($data['password'])) {
                    unset($data['password']);
                }

                $admin->update(collect($data)->only(['name', 'email', 'status', 'role_id', 'password'])->all());

                if ($request->hasFile('image')) {
                    foreach ($admin->files as $file) {
                        Storage::disk('documents')->delete($file->getAttributes()['document']);
                        $file->delete();
                    }

                    $uploadDocumentService->upload('admin', 'image', $admin->id, 'avatar', false, $request);
                }
            });

            return redirect(route('backend.admins.index'))->withSuccess(trans('backend.messages.success.update'));
        } catch (Throwable $e) {
            if ($e instanceof ValidationException) {
                return redirect()->back()->withErrors($e->errors())->withInput();
            }

            Log::error($e->getMessage());

            return redirect(route('backend.admins.index'))->withError(trans('backend.messages.error.update'));
        }
    }

    public function destroy(Admin $admin)
    {
        abort_if(Gate::denies('admins delete'), Response::HTTP_FORBIDDEN);
        abort_if($admin->isDeveloper(), Response::HTTP_FORBIDDEN);

        try {
            DB::transaction(function () use ($admin) {
                foreach ($admin->getDocuments('avatar') as $file) {
                    Storage::disk('documents')->delete($file->getAttributes()['document']);
                    $file->delete();
                }

                $admin->roles()->detach();
                $admin->delete();
            });

            return response(['status' => 1]);
        } catch (\Exception $e) {
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
            ->addColumn('name', function ($row) {
                $src = $row->first_image ?: asset('backend/img/noimage.jpg');

                return '
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                            <a href="' . route('backend.admins.edit', ['admin' => $row->id]) . '">
                                <div class="symbol-label">
                                    <img src="' . $src . '" class="w-100" />
                                </div>
                            </a>
                        </div>
                        <div class="d-flex flex-column">
                            <a href="' . route('backend.admins.edit', ['admin' => $row->id]) . '" class="text-gray-800 text-hover-primary mb-1 fw-bold">' . $row->name . '</a>
                            <span class="text-muted fw-semibold">' . $row->email . '</span>
                        </div>
                    </div>';
            })
            ->addColumn('role', fn ($row) => $row->role->name)
            ->addColumn('status', fn ($row) => badge($row->status))
            ->addColumn('actions', fn ($row) => $this->permissions($row->id))
            ->rawColumns(['checkbox', 'name', 'status', 'actions'])
            ->make(true);
    }

    protected function permissions($id): string
    {
        $edit_btn = '';
        $delete_btn = '';

        if (admin()->can('admins edit')) {
            $edit_btn = '<div class="menu-item px-3">
                            <a href="' . route('backend.admins.edit', ['admin' => $id]) . '" class="menu-link px-3">' . trans('backend.buttons.edit') . '</a>
                        </div>';
        }

        if (admin()->can('admins delete')) {
            $delete_btn = '<div class="menu-item px-3">
                            <a href="' . route('backend.admins.destroy', ['admin' => $id]) . '" class="menu-link px-3 btn-delete">' . trans('backend.buttons.delete') . '</a>
                        </div>';
        }

        return '<div>
                    <a href="#" class="btn btn-light btn-active-light-primary btn-flex btn-center btn-sm" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">' . trans('backend.labels.actions') . '
                    <i class="ki-outline ki-down fs-5 ms-1"></i></a>
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">
                        ' . $edit_btn . '
                        ' . $delete_btn . '
                    </div>
                </div>';
    }
}
