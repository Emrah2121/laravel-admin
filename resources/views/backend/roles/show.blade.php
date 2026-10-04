@extends('layouts.backend.master')
@section('title', trans('backend.titles.roles'))

@section('content')
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_toolbar" class="app-toolbar pt-7 pt-lg-10">
            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                    <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                        {{ $role->name }}
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
                        <li class="breadcrumb-item text-muted">@lang('backend.buttons.view')</li>
                    </ul>
                </div>
                <div class="d-flex align-items-center gap-2 gap-lg-3">
                    <a href="{{ route('backend.roles.edit', ['role' => $role->id]) }}" class="btn btn-sm fw-bold btn-primary">@lang('backend.buttons.edit')</a>
                    <a href="{{ route('backend.roles.index') }}" class="btn btn-sm fw-bold btn-secondary">@lang('backend.buttons.back')</a>
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
                                    <h2>@lang('backend.labels.details')</h2>
                                </div>
                            </div>
                            <div class="card-body pt-0 fs-6">
                                <div class="mb-5">
                                    <div class="fw-bold text-gray-800">@lang('backend.labels.name')</div>
                                    <div class="text-gray-600">{{ $role->name }}</div>
                                </div>
                                <div class="mb-5">
                                    <div class="fw-bold text-gray-800">@lang('backend.labels.total_users_with_role')</div>
                                    <div class="text-gray-600">{{ $role->users->count() }}</div>
                                </div>
                                <div>
                                    <div class="fw-bold text-gray-800">@lang('backend.labels.created_date')</div>
                                    <div class="text-gray-600">{{ $role->created_at->format('d.m.Y H:i') }}</div>
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
                                <div class="d-flex flex-column text-gray-600">
                                    @foreach ($role->permissions as $permission)
                                        <div class="d-flex align-items-center py-2">
                                            <span class="bullet bg-primary me-3"></span>{{ $permission->name }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
                        <div class="card card-flush py-4">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>@lang('backend.titles.admins')</h2>
                                </div>
                                <div class="card-toolbar">
                                    <span class="badge badge-success">@lang('backend.labels.total_users_with_role'): {{ $role->users->count() }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0 table-responsive">
                                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                                    <thead>
                                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                            <th class="min-w-50px">ID</th>
                                            <th class="min-w-150px">@lang('backend.titles.admins')</th>
                                            <th class="min-w-125px">@lang('backend.labels.created_at')</th>
                                        </tr>
                                    </thead>
                                    <tbody class="fw-semibold text-gray-600">
                                        @foreach ($role->users as $user)
                                            <tr>
                                                <td>{{ $user->id }}</td>
                                                <td class="d-flex align-items-center">
                                                    <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                                                        @if ($user->first_image)
                                                            <div class="symbol-label">
                                                                <img src="{{ asset($user->first_image) }}" alt="{{ $user->name }}" class="w-100" />
                                                            </div>
                                                        @else
                                                            <div class="symbol-label fs-3 bg-light-primary text-primary">
                                                                {{ substr($user->name, 0, 1) }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="d-flex flex-column">
                                                        <span class="text-gray-800 mb-1">{{ $user->name }}</span>
                                                        <span>{{ $user->email }}</span>
                                                    </div>
                                                </td>
                                                <td>{{ $user->created_at->format('d M Y, h:i a') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
