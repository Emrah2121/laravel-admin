@extends('layouts.backend.master')
@section('title', trans('backend.titles.permissions'))

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
                            <a href="{{ route('backend.permissions.index') }}" class="text-muted text-hover-primary">@lang('backend.titles.permissions')</a>
                        </li>
                        <li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
                        <li class="breadcrumb-item text-muted">{{ $edit ? trans('backend.buttons.edit') : trans('backend.buttons.create') }}</li>
                    </ul>
                </div>
            </div>
        </div>

        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                <form id="kt_permission_form" novalidate
                    action="{{ $edit === false ? route('backend.permissions.store') : route('backend.permissions.update', ['permission' => $permission->id]) }}"
                    method="POST" class="form">
                    @csrf
                    @if ($edit)
                        @method('PUT')
                    @endif

                    <div class="card card-flush py-4">
                        <div class="card-header">
                            <div class="card-title">
                                <h2>@lang('backend.labels.details')</h2>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="mb-10 fv-row">
                                <label class="required form-label">@lang('backend.labels.name')</label>
                                <input type="text" name="name" required
                                    class="form-control mb-2 @error('name') is-invalid @enderror"
                                    placeholder="@lang('backend.placeholders.enter.name')"
                                    value="{{ isset($permission) ? $permission->name : old('name') }}" />
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="fv-row">
                                <label class="required form-label">@lang('backend.labels.guard_name')</label>
                                <input type="text" name="guard_name" required
                                    class="form-control mb-2 @error('guard_name') is-invalid @enderror"
                                    placeholder="@lang('backend.placeholders.enter.guard_name')"
                                    value="{{ isset($permission) ? $permission->guard_name : old('guard_name', 'admin') }}" />
                                @error('guard_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-7">
                        <a href="{{ route('backend.permissions.index') }}" class="btn btn-light me-5">@lang('backend.buttons.cancel')</a>
                        <button type="submit" class="btn btn-primary" id="kt_permission_submit">
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
        $('#kt_permission_form').on('submit', function(e) {
            if (!ktValidateRequired(this)) {
                e.preventDefault();
                return;
            }

            const submitButton = $('#kt_permission_submit');
            submitButton.attr('data-kt-indicator', 'on');
            submitButton.prop('disabled', true);
        });
    </script>
@endsection
