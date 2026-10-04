@extends('layouts.backend.master')
@section('title', trans('backend.titles.admins'))
@section('styles')
    <style>
        .image-input-placeholder {
            background-image: url('{{ asset('backend/assets/media/svg/files/blank-image.svg') }}');
        }

        [data-bs-theme="dark"] .image-input-placeholder {
            background-image: url('{{ asset('backend/assets/media/svg/files/blank-image-dark.svg') }}');
        }
    </style>
@endsection
@section('content')
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_toolbar" class="app-toolbar pt-7 pt-lg-10">
            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                    <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                        @lang('backend.titles.admins')
                    </h1>
                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ route('backend.dashboard') }}"
                                class="text-muted text-hover-primary">@lang('backend.titles.dashboard')</a>
                        </li>
                        <li class="breadcrumb-item">
                            <span class="bullet bg-gray-400 w-5px h-2px"></span>
                        </li>
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ route('backend.admins.index') }}"
                                class="text-muted text-hover-primary">@lang('backend.titles.admins')</a>
                        </li>
                        <li class="breadcrumb-item">
                            <span class="bullet bg-gray-400 w-5px h-2px"></span>
                        </li>
                        <li class="breadcrumb-item text-muted">
                            {{ $edit ? trans('backend.buttons.edit') : trans('backend.buttons.create') }}</li>
                    </ul>
                </div>
            </div>
        </div>
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                <form id="kt_admin_form" autocomplete="off" novalidate
                    action="{{ $edit === false ? route('backend.admins.store') : route('backend.admins.update', ['admin' => $admin->id]) }}"
                    method="POST" enctype="multipart/form-data" class="form d-flex flex-column flex-lg-row">
                    @csrf
                    @if ($edit)
                        @method('PUT')
                    @endif

                    <div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">
                        <div class="card card-flush py-4">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>@lang('backend.labels.image')</h2>
                                </div>
                            </div>
                            <div class="card-body text-center pt-0">
                                <div class="image-input {{ isset($admin) && $admin->first_image ? 'image-input-outline' : 'image-input-empty image-input-outline' }} image-input-placeholder mb-3"
                                    data-kt-image-input="true">
                                    <div class="image-input-wrapper w-150px h-150px"
                                        style="background-image: url('{{ isset($admin) && $admin->first_image ? asset($admin->first_image) : asset('backend/img/noimage.jpg') }}');">
                                    </div>
                                    <label
                                        class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="change" data-bs-toggle="tooltip"
                                        title="@lang('backend.placeholders.choose.image')">
                                        <i class="ki-outline ki-pencil fs-7"></i>
                                        <input type="file" name="image[]" accept="image/*" />
                                        <input type="hidden" name="image_remove" />
                                    </label>
                                    <span
                                        class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel image">
                                        <i class="ki-outline ki-cross fs-2"></i>
                                    </span>
                                    <span
                                        class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove image">
                                        <i class="ki-outline ki-cross fs-2"></i>
                                    </span>
                                </div>
                                <div class="text-muted fs-7">@lang('backend.notes.only_image_files')</div>
                                @error('image')
                                    <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                                @enderror
                                @error('image.*')
                                    <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        @if ($edit)
                            @include('backend.includes.media', [
                                'files' => $admin->getDocuments('avatar'),
                                'media_collection_name' => 'avatar',
                                'isDeleted' => true,
                                'isCovered' => false,
                            ])
                        @endif
                        <div class="card card-flush py-4">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>@lang('backend.labels.status')</h2>
                                </div>
                                <div class="card-toolbar">
                                    <div class="rounded-circle bg-success w-15px h-15px"
                                        id="kt_ecommerce_add_product_status_indicator"></div>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <select name="status" class="form-select mb-2" data-control="select2"
                                    data-hide-search="true" data-placeholder="Select an option">
                                    <option value="1"
                                        {{ (isset($admin) ? $admin->status : old('status', 1)) == 1 ? 'selected' : '' }}>
                                        @lang('backend.mixins.active')</option>
                                    <option value="0"
                                        {{ (isset($admin) ? $admin->status : old('status', 1)) == 0 ? 'selected' : '' }}>
                                        @lang('backend.mixins.passive')</option>
                                </select>
                                <div class="text-muted fs-7">@lang('backend.labels.status')</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
                        <div class="card card-flush py-4">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>@lang('backend.labels.general_information')</h2>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="mb-10 fv-row">
                                    <label class="required form-label">@lang('backend.labels.name')</label>
                                    <input type="text" name="name" required
                                        class="form-control mb-2 @error('name') is-invalid @enderror"
                                        placeholder="@lang('backend.placeholders.enter.name')"
                                        value="{{ isset($admin) ? $admin->name : old('name') }}" />
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-10 fv-row">
                                    <label class="required form-label">@lang('backend.labels.email')</label>
                                    <input type="email" name="email" autocomplete="off" required
                                        class="form-control mb-2 @error('email') is-invalid @enderror"
                                        placeholder="@lang('backend.placeholders.enter.email')"
                                        value="{{ isset($admin) ? $admin->email : old('email') }}" />
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-10 fv-row">
                                    <label
                                        class="@if (!$edit) required @endif form-label">@lang('backend.labels.password')</label>
                                    <div class="d-flex gap-2 mb-2">
                                        <input type="password" name="password" id="password_input" autocomplete="new-password"
                                            @if (!$edit) required @endif
                                            class="form-control @error('password') is-invalid @enderror"
                                            placeholder="@lang('backend.placeholders.enter.password')" />
                                        <button type="button" class="btn btn-light-primary flex-shrink-0" id="generate_password_btn">
                                            <i class="ki-outline ki-arrows-circle fs-4"></i> @lang('backend.buttons.generate_password')
                                        </button>
                                        <button type="button" class="btn btn-light flex-shrink-0" id="copy_password_btn">
                                            <i class="ki-outline ki-copy fs-4"></i> @lang('backend.buttons.copy')
                                        </button>
                                    </div>
                                    @error('password')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    @if ($edit)
                                        <div class="text-muted fs-7">@lang('backend.labels.password_help')</div>
                                    @endif
                                </div>
                                <div class="mb-10 fv-row">
                                    <label class="required form-label">@lang('backend.labels.role')</label>
                                    <select name="role_id" required class="form-select mb-2 @error('role_id') is-invalid @enderror"
                                        data-control="select2" data-hide-search="true"
                                        data-placeholder="@lang('backend.placeholders.select.role')">
                                        <option></option>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->id }}"
                                                {{ (isset($admin) ? optional($admin->role)->id : old('role_id')) == $role->id ? 'selected' : '' }}>
                                                {{ $role->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('role_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('backend.admins.index') }}"
                                class="btn btn-light me-5">@lang('backend.buttons.cancel')</a>
                            <button type="submit" id="kt_admins_submit" class="btn btn-primary">
                                <span class="indicator-label">@lang('backend.buttons.save')</span>
                                <span class="indicator-progress">@lang('backend.labels.please_wait')...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        $('#kt_admin_form').on('submit', function(e) {
            if (!ktValidateRequired(this)) {
                e.preventDefault();
                return;
            }

            const submitButton = $('#kt_admins_submit');
            submitButton.attr('data-kt-indicator', 'on');
            submitButton.prop('disabled', true);
        });

        function generateStrongPassword(length = 12) {
            const lower = 'abcdefghijklmnopqrstuvwxyz';
            const upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            const digits = '0123456789';
            const symbols = '!@#$%^&*()-+=';
            const all = lower + upper + digits + symbols;

            let password = [
                upper[Math.floor(Math.random() * upper.length)],
                lower[Math.floor(Math.random() * lower.length)],
                digits[Math.floor(Math.random() * digits.length)],
                symbols[Math.floor(Math.random() * symbols.length)],
            ];

            for (let i = password.length; i < length; i++) {
                password.push(all[Math.floor(Math.random() * all.length)]);
            }

            for (let i = password.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [password[i], password[j]] = [password[j], password[i]];
            }

            return password.join('');
        }

        $('#generate_password_btn').on('click', function() {
            const input = document.getElementById('password_input');
            input.type = 'text';
            input.value = generateStrongPassword();
        });

        $('#copy_password_btn').on('click', function() {
            const input = document.getElementById('password_input');
            if (!input.value) {
                return;
            }

            navigator.clipboard.writeText(input.value).then(function() {
                const btn = $('#copy_password_btn');
                const originalHtml = btn.html();
                btn.html('<i class="ki-outline ki-check fs-4"></i> @lang('backend.buttons.copied')');
                setTimeout(function() {
                    btn.html(originalHtml);
                }, 1500);
            });
        });
    </script>
@endsection
