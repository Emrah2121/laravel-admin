@extends('layouts.backend.master')
@section('title', trans('backend.titles.crud_generator'))

@section('content')
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_toolbar" class="app-toolbar pt-7 pt-lg-10">
            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                    <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                        @lang('backend.titles.crud_generator')
                    </h1>
                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ route('backend.dashboard') }}" class="text-muted text-hover-primary">@lang('backend.titles.dashboard')</a>
                        </li>
                        <li class="breadcrumb-item">
                            <span class="bullet bg-gray-400 w-5px h-2px"></span>
                        </li>
                        <li class="breadcrumb-item text-muted">@lang('backend.titles.crud_generator')</li>
                    </ul>
                </div>

                <div class="d-flex align-items-center gap-2 gap-lg-3">
                    <a href="{{ route('backend.crud_generator.create') }}" class="btn btn-sm fw-bold btn-primary">
                        <i class="ki-outline ki-plus fs-2"></i> @lang('backend.crud_generator.new_module')
                    </a>
                </div>
            </div>
        </div>

        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                <div class="card card-flush">
                    <div class="card-header pt-6">
                        <div class="card-title">
                            <h2>@lang('backend.crud_generator.modules')</h2>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        @if (empty($modules))
                            <div class="text-muted fst-italic py-10 text-center">@lang('backend.crud_generator.no_modules')</div>
                        @else
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                    <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-150px">@lang('backend.crud_generator.module')</th>
                                        <th class="min-w-150px">@lang('backend.crud_generator.fields')</th>
                                        <th class="min-w-150px">@lang('backend.crud_generator.generated_at')</th>
                                        <th class="min-w-100px text-end">@lang('backend.labels.actions')</th>
                                    </tr>
                                </thead>
                                <tbody class="fw-semibold text-gray-600">
                                    @foreach ($modules as $module)
                                        <tr>
                                            <td>
                                                <div class="text-gray-800 fw-bold">{{ $module['name'] }}</div>
                                                <div class="text-muted fs-7">{{ $module['key'] }}</div>
                                            </td>
                                            <td>{{ count($module['spec']['fields'] ?? []) }}</td>
                                            <td>{{ $module['generated_at'] ?? '—' }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('backend.crud_generator.show', ['module' => $module['name']]) }}"
                                                    class="btn btn-sm btn-light btn-active-light-primary">@lang('backend.buttons.view')</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
