@extends('layouts.backend.master')
@section('title', trans('backend.titles.blogs'))

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
                        @lang('backend.titles.blogs')
                    </h1>
                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ route('backend.dashboard') }}" class="text-muted text-hover-primary">@lang('backend.titles.dashboard')</a>
                        </li>
                        <li class="breadcrumb-item">
                            <span class="bullet bg-gray-400 w-5px h-2px"></span>
                        </li>
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ route('backend.blogs.index') }}" class="text-muted text-hover-primary">@lang('backend.titles.blogs')</a>
                        </li>
                        <li class="breadcrumb-item">
                            <span class="bullet bg-gray-400 w-5px h-2px"></span>
                        </li>
                        <li class="breadcrumb-item text-muted">@lang('backend.buttons.view')</li>
                    </ul>
                </div>

                <div class="d-flex align-items-center gap-2 gap-lg-3">
                    <a href="{{ route('backend.blogs.edit', ['blog' => $blog->id]) }}" class="btn btn-sm fw-bold btn-primary">@lang('backend.buttons.edit')</a>
                    <a href="{{ route('backend.blogs.index') }}" class="btn btn-sm fw-bold btn-secondary">@lang('backend.buttons.back')</a>
                </div>
            </div>
        </div>

        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                @include('backend.blogs.tables.show')
            </div>
        </div>
    </div>
@endsection
