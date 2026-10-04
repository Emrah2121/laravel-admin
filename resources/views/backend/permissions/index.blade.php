@extends('layouts.backend.master')
@section('title', trans('backend.titles.permissions'))
@section('content')
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_toolbar" class="app-toolbar pt-7 pt-lg-10">
            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                    <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                        @lang('backend.titles.permissions')
                    </h1>
                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ route('backend.dashboard') }}" class="text-muted text-hover-primary">@lang('backend.titles.dashboard')</a>
                        </li>
                        <li class="breadcrumb-item">
                            <span class="bullet bg-gray-400 w-5px h-2px"></span>
                        </li>
                        <li class="breadcrumb-item text-muted">@lang('backend.titles.permissions')</li>
                    </ul>
                </div>
            </div>
        </div>

        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                <div class="card">
                    @include('backend.includes.card.header', ['page' => 'permissions'])
                    @include('backend.permissions.tables.index')
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @include('backend.includes.plugins.datatable', [
        'columns' => ['checkbox', 'name', 'guard_name', 'assigned_to', 'created_at', 'actions'],
        'route' => route('backend.permissions.index'),
        'pageLength' => 10,
    ])
@endsection
