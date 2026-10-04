@extends('layouts.backend.master')
@section('title', trans('backend.titles.crud_generator'))

@section('content')
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_toolbar" class="app-toolbar pt-7 pt-lg-10">
            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                    <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                        {{ $spec->name }}
                    </h1>
                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ route('backend.dashboard') }}" class="text-muted text-hover-primary">@lang('backend.titles.dashboard')</a>
                        </li>
                        <li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ route('backend.crud_generator.index') }}" class="text-muted text-hover-primary">@lang('backend.titles.crud_generator')</a>
                        </li>
                        <li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
                        <li class="breadcrumb-item text-muted">{{ $spec->name }}</li>
                    </ul>
                </div>

                <div class="d-flex align-items-center gap-2 gap-lg-3">
                    @if ($moduleUrl)
                        <a href="{{ $moduleUrl }}" class="btn btn-sm fw-bold btn-primary">@lang('backend.crud_generator.step_open')</a>
                    @endif
                    <a href="{{ route('backend.crud_generator.index') }}" class="btn btn-sm fw-bold btn-secondary">@lang('backend.buttons.back')</a>
                </div>
            </div>
        </div>

        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl d-flex flex-column gap-7 gap-lg-10">
                @if ($result)
                    <div class="card card-flush py-4">
                        <div class="card-header">
                            <div class="card-title">
                                <h2>@lang('backend.crud_generator.next_steps')</h2>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <ol class="mb-0">
                                @if (!$migrated)
                                    <li><code>@lang('backend.crud_generator.step_migrate')</code></li>
                                @endif
                                @if (!$seeded)
                                    <li><code>@lang('backend.crud_generator.step_seed')</code></li>
                                @endif
                                @if ($moduleUrl)
                                    <li><a href="{{ $moduleUrl }}">@lang('backend.crud_generator.step_open')</a></li>
                                @endif
                            </ol>

                            @foreach ($result['artisan'] as $command => $run)
                                <div class="fw-bold mt-5 mb-2 {{ $run['successful'] ? 'text-gray-800' : 'text-danger' }}">
                                    @lang('backend.crud_generator.artisan_output'): {{ $command }}
                                </div>
                                <pre class="bg-light rounded p-3 mb-0">{{ $run['output'] }}</pre>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="card card-flush py-4">
                    <div class="card-header">
                        <div class="card-title">
                            <h2>@lang('backend.crud_generator.fields')</h2>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <table class="table align-middle table-row-dashed fs-6 gy-3 mb-0">
                            <thead>
                                <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                    <th>@lang('backend.crud_generator.field_name')</th>
                                    <th>@lang('backend.crud_generator.field_label')</th>
                                    <th>@lang('backend.crud_generator.type')</th>
                                    <th>@lang('backend.crud_generator.required')</th>
                                    <th>@lang('backend.crud_generator.translatable')</th>
                                    <th>@lang('backend.crud_generator.quill')</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-700">
                                @foreach ($spec->fields as $field)
                                    <tr>
                                        <td><code>{{ $field->name }}</code></td>
                                        <td>{{ $field->label }}</td>
                                        <td>@lang('backend.crud_generator.type_' . $field->type){{ $field->maxLength ? ' (' . $field->maxLength . ')' : '' }}</td>
                                        <td>{{ $field->required ? trans('backend.labels.yes') : trans('backend.labels.no') }}</td>
                                        <td>{{ $field->translatable ? trans('backend.labels.yes') : trans('backend.labels.no') }}</td>
                                        <td>{{ $field->quill ? trans('backend.labels.yes') : trans('backend.labels.no') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card card-flush py-4">
                    <div class="card-header">
                        <div class="card-title">
                            <h2>@lang('backend.crud_generator.created') ({{ count($entry['files']) }})</h2>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <ul class="mb-0">
                            @foreach ($entry['files'] as $path)
                                <li><code>{{ $path }}</code></li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="card card-flush py-4">
                    <div class="card-header">
                        <div class="card-title">
                            <h2>@lang('backend.crud_generator.modified') ({{ count($entry['regions']) }})</h2>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <ul class="mb-0">
                            @foreach ($entry['regions'] as $region)
                                <li><code>{{ $region['path'] }}</code> <span class="text-muted fs-7">{{ $region['marker'] }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
