@extends('layouts.backend.master')
@section('title', trans('backend.titles.languages'))
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
                            <a href="{{ route('backend.dashboard') }}"
                                class="text-muted text-hover-primary">@lang('backend.titles.dashboard')</a>
                        </li>
                        <li class="breadcrumb-item">
                            <span class="bullet bg-gray-400 w-5px h-2px"></span>
                        </li>
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ route('backend.languages.index') }}"
                                class="text-muted text-hover-primary">@lang('backend.titles.languages')</a>
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
                <form id="kt_language_form" novalidate
                    action="{{ $edit === false ? route('backend.languages.store') : route('backend.languages.update', ['language' => $language->id]) }}"
                    method="POST" class="form d-flex flex-column flex-lg-row">
                    @csrf
                    @if ($edit)
                        @method('PUT')
                    @endif

                    <div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">
                        <div class="card card-flush py-4">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>@lang('backend.labels.status')</h2>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <select class="form-select mb-2" name="status" data-control="select2"
                                    data-hide-search="true">
                                    <option value="0"
                                        {{ (isset($language) && !$language->status) || old('status') == 0 ? 'selected' : '' }}>
                                        @lang('backend.labels.passive')</option>
                                    <option value="1"
                                        {{ (isset($language) && $language->status) || old('status') == 1 ? 'selected' : '' }}>
                                        @lang('backend.labels.active')</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="card card-flush py-4">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>@lang('backend.labels.default')</h2>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <select class="form-select mb-2" name="default" data-control="select2"
                                    data-hide-search="true">
                                    <option value="0"
                                        {{ (isset($language) && !$language->default) || old('default') == 0 ? 'selected' : '' }}>
                                        @lang('backend.labels.passive')</option>
                                    <option value="1"
                                        {{ (isset($language) && $language->default) || old('default') == 1 ? 'selected' : '' }}>
                                        @lang('backend.labels.active')</option>
                                </select>
                                @error('default')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
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
                                <div class="fv-row mb-10">
                                    <label class="required form-label">@lang('backend.labels.name')</label>
                                    <input type="text" name="name" required class="form-control mb-2"
                                        placeholder="@lang('backend.placeholders.enter.name')"
                                        value="{{ isset($language) ? $language->name : old('name') }}" />
                                    @error('name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="fv-row mb-10">
                                    <label class="required form-label">@lang('backend.labels.code')</label>
                                    <input type="text" name="code" required class="form-control mb-2"
                                        placeholder="@lang('backend.placeholders.enter.code')"
                                        value="{{ isset($language) ? $language->code : old('code') }}" />
                                    @error('code')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('backend.languages.index') }}"
                                class="btn btn-light me-5">@lang('backend.buttons.cancel')</a>
                            <button type="submit" id="kt_language_submit" class="btn btn-primary">
                                <span class="indicator-label">@lang('backend.buttons.save')</span>
                                <span class="indicator-progress">@lang('backend.labels.please_wait')
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
        $('#kt_language_form').on('submit', function(e) {
            if (!ktValidateRequired(this)) {
                e.preventDefault();
                return;
            }

            const submitButton = $('#kt_language_submit');
            submitButton.attr('data-kt-indicator', 'on');
            submitButton.prop('disabled', true);
        });
    </script>
@endsection
