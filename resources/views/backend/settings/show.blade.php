@extends('layouts.backend.master')
@section('title', trans('backend.titles.settings'))

@section('content')
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_toolbar" class="app-toolbar pt-7 pt-lg-10">
            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                    <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                        @lang('backend.titles.settings') - {{ $setting->name }}
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
                            <a href="{{ route('backend.settings.index') }}"
                                class="text-muted text-hover-primary">@lang('backend.titles.settings')</a>
                        </li>
                        <li class="breadcrumb-item">
                            <span class="bullet bg-gray-400 w-5px h-2px"></span>
                        </li>
                        <li class="breadcrumb-item text-muted">@lang('backend.buttons.view')</li>
                    </ul>
                </div>
                <div class="d-flex align-items-center gap-2 gap-lg-3">
                    <a href="{{ route('backend.settings.edit', ['setting' => $setting->id]) }}"
                        class="btn btn-sm fw-bold btn-primary">@lang('backend.buttons.edit')</a>
                    <a href="{{ route('backend.settings.index') }}"
                        class="btn btn-sm fw-bold btn-secondary">@lang('backend.buttons.back')</a>
                </div>
            </div>
        </div>

        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                <div class="d-flex flex-column flex-lg-row">
                    <div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">
                        <div class="card card-flush py-4">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>@lang('backend.labels.general_information')</h2>
                                </div>
                            </div>
                            <div class="card-body pt-0 fs-6">
                                <div class="mb-5">
                                    <div class="fw-bold text-gray-800">@lang('backend.labels.name')</div>
                                    <div class="text-gray-600">{{ $setting->name }}</div>
                                </div>
                                <div class="mb-5">
                                    <div class="fw-bold text-gray-800">Slug</div>
                                    <div class="text-gray-600">{{ $setting->slug }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="card card-flush py-4">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>@lang('backend.labels.details')</h2>
                                </div>
                            </div>
                            <div class="card-body pt-0 fs-6">
                                <div class="mb-5">
                                    <div class="fw-bold text-gray-800">@lang('backend.labels.created_at')</div>
                                    <div class="text-gray-600">{{ $setting->created_at->format('d.m.Y H:i') }}</div>
                                </div>
                                <div class="mb-5">
                                    <div class="fw-bold text-gray-800">@lang('backend.labels.updated_at')</div>
                                    <div class="text-gray-600">{{ $setting->updated_at->format('d.m.Y H:i') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
                        <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-n2">
                            @foreach ($langs as $lang)
                                <li class="nav-item">
                                    <a class="nav-link text-active-primary pb-4 @if ($loop->first) active @endif"
                                        data-bs-toggle="tab" href="#kt_setting_lang_{{ $lang->code }}">
                                        {{ $lang->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        <div class="tab-content">
                            @foreach ($langs as $lang)
                                <div class="tab-pane fade @if ($loop->first) show active @endif"
                                    id="kt_setting_lang_{{ $lang->code }}" role="tabpanel">
                                    <div class="d-flex flex-column gap-7 gap-lg-10">
                                        <div class="card card-flush py-4">
                                            <div class="card-header">
                                                <div class="card-title">
                                                    <h2>{{ $lang->name }} @lang('backend.labels.content')</h2>
                                                </div>
                                            </div>
                                            <div class="card-body pt-0">
                                                @if ($translations[$lang->code])
                                                    <div class="mb-10">
                                                        <label class="fw-bold fs-6 mb-2">@lang('backend.labels.content')</label>
                                                        <div class="text-gray-600 border p-5 rounded bg-light bg-opacity-50">
                                                            {!! $translations[$lang->code]->content !!}
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="text-muted fst-italic">@lang('backend.messages.error.not_found')</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
