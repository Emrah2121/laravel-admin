@extends('layouts.backend.master')
@section('title', trans('backend.titles.roles'))

@section('content')
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_toolbar" class="app-toolbar pt-7 pt-lg-10">
            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                    <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                        {{ $edit ? trans('backend.buttons.edit') : trans('backend.buttons.create') }}
                    </h1>
                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ route('backend.dashboard') }}" class="text-muted text-hover-primary">@lang('backend.titles.dashboard')</a>
                        </li>
                        <li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ route('backend.roles.index') }}" class="text-muted text-hover-primary">@lang('backend.titles.roles')</a>
                        </li>
                        <li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
                        <li class="breadcrumb-item text-muted">{{ $edit ? trans('backend.buttons.edit') : trans('backend.buttons.create') }}</li>
                    </ul>
                </div>
            </div>
        </div>

        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                <form id="kt_role_form" novalidate
                    action="{{ $edit === false ? route('backend.roles.store') : route('backend.roles.update', ['role' => $role->id]) }}"
                    method="POST" class="form">
                    @csrf
                    @if ($edit)
                        @method('PUT')
                    @endif

                    <div class="card card-flush py-4 mb-7">
                        <div class="card-header">
                            <div class="card-title">
                                <h2>@lang('backend.labels.details')</h2>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="fv-row">
                                <label class="required form-label">@lang('backend.labels.name')</label>
                                <input type="text" name="name" required
                                    class="form-control mb-2 @error('name') is-invalid @enderror"
                                    placeholder="@lang('backend.placeholders.enter.name')"
                                    value="{{ isset($role) ? $role->name : old('name') }}" />
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="card card-flush py-4">
                        <div class="card-header">
                            <div class="card-title">
                                <h2>@lang('backend.labels.permissions')</h2>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed fs-6 gy-5">
                                    <tbody class="text-gray-600 fw-semibold">
                                        <tr>
                                            <td class="text-gray-800">
                                                @lang('backend.labels.permissions')
                                            </td>
                                            <td>
                                                <label class="form-check form-check-custom form-check-solid me-9">
                                                    <input class="form-check-input" type="checkbox" id="kt_roles_select_all" />
                                                    <span class="form-check-label" for="kt_roles_select_all">@lang('backend.mixins.check_uncheck_all')</span>
                                                </label>
                                            </td>
                                        </tr>
                                        @foreach ($permissions->groupBy(fn($item) => explode(' ', $item->name)[0]) as $groupName => $groupPermissions)
                                            <tr>
                                                <td class="text-gray-800 text-capitalize">{{ $groupName }}</td>
                                                <td>
                                                    <div class="d-flex flex-wrap">
                                                        @foreach ($groupPermissions as $permission)
                                                            <label class="form-check form-check-sm form-check-custom form-check-solid me-5 me-lg-10 mb-2">
                                                                <input class="form-check-input permission-checkbox" type="checkbox"
                                                                    value="{{ $permission->id }}" name="permissions[]"
                                                                    id="permission_{{ $permission->id }}"
                                                                    {{ isset($rolePermissions) && in_array($permission->id, $rolePermissions) ? 'checked' : '' }} />
                                                                <span class="form-check-label text-capitalize">
                                                                    {{ str_replace($groupName . ' ', '', $permission->name) }}
                                                                </span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @error('permissions')
                                <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-7">
                        <a href="{{ route('backend.roles.index') }}" class="btn btn-light me-5">@lang('backend.buttons.cancel')</a>
                        <button type="submit" class="btn btn-primary" id="kt_role_submit">
                            <span class="indicator-label">@lang('backend.buttons.save')</span>
                            <span class="indicator-progress">@lang('backend.labels.please_wait')
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            const selectAll = document.getElementById('kt_roles_select_all');
            const checkboxes = document.querySelectorAll('.permission-checkbox');

            if (selectAll) {
                selectAll.checked = checkboxes.length > 0 && [...checkboxes].every(c => c.checked);
                selectAll.addEventListener('change', e => {
                    checkboxes.forEach(c => c.checked = e.target.checked);
                });
            }

            $('#kt_role_form').on('submit', function(e) {
                if (!ktValidateRequired(this)) {
                    e.preventDefault();
                    return;
                }

                const checkedPermissions = document.querySelectorAll('.permission-checkbox:checked');
                if (checkedPermissions.length === 0) {
                    e.preventDefault();
                    Swal.fire({
                        icon: "error",
                        text: "@lang('backend.messages.warning.permissions')",
                        buttonsStyling: false,
                        confirmButtonText: "@lang('backend.buttons.got_it')",
                        customClass: {
                            confirmButton: "btn fw-bold btn-primary"
                        }
                    });
                    return;
                }

                const submitButton = $('#kt_role_submit');
                submitButton.attr('data-kt-indicator', 'on');
                submitButton.prop('disabled', true);
            });
        });
    </script>
@endsection
