@extends('layouts.backend.master')
@section('title', trans('backend.titles.dashboard'))
@section('content')
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_toolbar" class="app-toolbar pt-7 pt-lg-10">
            <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                    <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                        @lang('backend.titles.dashboard')
                    </h1>
                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ route('backend.dashboard') }}" class="text-muted text-hover-primary">@lang('backend.titles.dashboard')</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div id="kt_app_content" class="app-content">
            <div id="kt_app_content_container" class="app-container container-fluid">
                <div class="row g-5 g-xl-10">

                    @can('blogs index')
                        <div class="col-md-6 col-xl-4 mb-xl-10">
                            <a href="{{ route('backend.blogs.index') }}" class="card card-flush h-md-100 mb-5 mb-xl-10">
                                <div class="card-body d-flex flex-column align-items-start py-9 px-9">
                                    <div class="symbol symbol-50px mb-5">
                                        <span class="symbol-label bg-light-success">
                                            <i class="ki-outline ki-book-square fs-2x text-success"></i>
                                        </span>
                                    </div>
                                    <div class="fs-2hx fw-bold text-gray-800 mb-2 lh-1">{{ $counts['blogs'] }}</div>
                                    <span class="fs-6 fw-semibold text-gray-500">@lang('backend.titles.blogs')</span>
                                </div>
                            </a>
                        </div>
                    @endcan

                    @can('settings index')
                        <div class="col-md-6 col-xl-4 mb-xl-10">
                            <a href="{{ route('backend.settings.index') }}" class="card card-flush h-md-100 mb-5 mb-xl-10">
                                <div class="card-body d-flex flex-column align-items-start py-9 px-9">
                                    <div class="symbol symbol-50px mb-5">
                                        <span class="symbol-label bg-light-dark">
                                            <i class="ki-outline ki-setting-2 fs-2x text-dark"></i>
                                        </span>
                                    </div>
                                    <div class="fs-2hx fw-bold text-gray-800 mb-2 lh-1">-</div>
                                    <span class="fs-6 fw-semibold text-gray-500">@lang('backend.titles.settings')</span>
                                </div>
                            </a>
                        </div>
                    @endcan

                </div>
            </div>
        </div>
    </div>
@endsection
