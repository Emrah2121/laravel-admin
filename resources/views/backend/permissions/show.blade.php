@extends('layouts.backend.master')
@section('title', trans('backend.titles.permissions'))

@section('content')
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_toolbar" class="app-toolbar pt-7 pt-lg-10">
            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                    <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                        @lang('backend.titles.permissions') - {{ $permission->id }}
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
                        <li class="breadcrumb-item text-muted">@lang('backend.buttons.view')</li>
                    </ul>
                </div>
                <div class="d-flex align-items-center gap-2 gap-lg-3">
                    <a href="{{ route('backend.permissions.edit', ['permission' => $permission->id]) }}" class="btn btn-sm fw-bold btn-primary">@lang('backend.buttons.edit')</a>
                    <a href="{{ route('backend.permissions.index') }}" class="btn btn-sm fw-bold btn-secondary">@lang('backend.buttons.back')</a>
                </div>
            </div>
        </div>

        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                <div class="card card-flush py-4">
                    <div class="card-header">
                        <div class="card-title">
                            <h2>@lang('backend.labels.details')</h2>
                        </div>
                    </div>
                    <div class="card-body pt-0 fs-6">
                        <div class="mb-5">
                            <div class="fw-bold text-gray-800">@lang('backend.labels.name')</div>
                            <div class="text-gray-600">{{ $permission->name }}</div>
                        </div>
                        <div class="mb-5">
                            <div class="fw-bold text-gray-800">@lang('backend.labels.guard_name')</div>
                            <div class="text-gray-600">{{ $permission->guard_name }}</div>
                        </div>
                        <div class="mb-5">
                            <div class="fw-bold text-gray-800">@lang('backend.labels.assigned_to')</div>
                            <div class="text-gray-600">
                                @forelse ($permission->roles as $role)
                                    <span class="badge badge-light-primary me-1 mb-1">{{ $role->name }}</span>
                                @empty
                                    &mdash;
                                @endforelse
                            </div>
                        </div>
                        <div>
                            <div class="fw-bold text-gray-800">@lang('backend.labels.created_date')</div>
                            <div class="text-gray-600">{{ $permission->created_at->format('d.m.Y H:i') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
