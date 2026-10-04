@extends('layouts.backend.master')
@section('title', trans('backend.titles.smart_seeder'))

@section('content')
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_toolbar" class="app-toolbar pt-7 pt-lg-10">
            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                    <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                        @lang('backend.titles.smart_seeder')
                    </h1>
                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ route('backend.dashboard') }}" class="text-muted text-hover-primary">@lang('backend.titles.dashboard')</a>
                        </li>
                        <li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
                        <li class="breadcrumb-item text-muted">@lang('backend.titles.smart_seeder')</li>
                    </ul>
                </div>
            </div>
        </div>

        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl d-flex flex-column gap-7 gap-lg-10">
                @if ($errors->any())
                    <div class="alert alert-danger mb-0">
                        @foreach ($errors->all() as $message)
                            <div>{{ $message }}</div>
                        @endforeach
                    </div>
                @endif

                <div class="row g-7 g-lg-10">
                    <div class="col-lg-5">
                        <form action="{{ route('backend.smart_seeder.settings') }}" method="POST" class="card card-flush h-100 py-4" autocomplete="off">
                            @csrf
                            @method('PUT')
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>@lang('backend.smart_seeder.settings')</h2>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="mb-7">
                                    <label class="form-label">
                                        @lang('backend.smart_seeder.gemini_api_key')
                                        <span class="badge {{ $settings->hasGeminiKey() ? 'badge-light-success' : 'badge-light-danger' }} ms-2">
                                            {{ $settings->hasGeminiKey() ? trans('backend.smart_seeder.key_saved') : trans('backend.smart_seeder.key_missing') }}
                                        </span>
                                    </label>
                                    <input type="password" name="gemini_api_key" class="form-control" autocomplete="new-password" />
                                </div>
                                <div class="mb-7">
                                    <label class="required form-label">@lang('backend.smart_seeder.gemini_model')</label>
                                    <input type="text" name="gemini_model" required class="form-control"
                                        value="{{ old('gemini_model', $settings->gemini_model) }}" />
                                </div>
                                <div class="mb-5">
                                    <label class="form-label">
                                        @lang('backend.smart_seeder.pexels_api_key')
                                        <span class="badge {{ $settings->hasPexelsKey() ? 'badge-light-success' : 'badge-light-danger' }} ms-2">
                                            {{ $settings->hasPexelsKey() ? trans('backend.smart_seeder.key_saved') : trans('backend.smart_seeder.key_missing') }}
                                        </span>
                                    </label>
                                    <input type="password" name="pexels_api_key" class="form-control" autocomplete="new-password" />
                                </div>
                                <div class="text-muted fs-7 mb-5">@lang('backend.smart_seeder.keys_hint')</div>
                                <button type="submit" class="btn btn-light-primary">@lang('backend.buttons.save')</button>
                            </div>
                        </form>
                    </div>

                    <div class="col-lg-7">
                        <form action="{{ route('backend.smart_seeder.store') }}" method="POST" class="card card-flush h-100 py-4" id="kt_smart_seeder_form">
                            @csrf
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>@lang('backend.smart_seeder.generate')</h2>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-5 mb-7">
                                    <div class="col-md-8">
                                        <label class="required form-label">@lang('backend.smart_seeder.module')</label>
                                        <select name="module" class="form-select" data-control="select2" data-hide-search="true" required>
                                            @foreach ($modules as $name => $label)
                                                <option value="{{ $name }}" @selected(old('module') === $name)>{{ $label }} ({{ $name }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="required form-label">@lang('backend.smart_seeder.count')</label>
                                        <input type="number" name="count" min="1" max="{{ $maxRecords }}" required
                                            class="form-control" value="{{ old('count', 5) }}" />
                                    </div>
                                </div>
                                <div class="mb-7">
                                    <label class="required form-label">@lang('backend.smart_seeder.instruction')</label>
                                    <textarea name="instruction" rows="4" maxlength="1000" required class="form-control"
                                        placeholder="@lang('backend.smart_seeder.instruction_placeholder')">{{ old('instruction') }}</textarea>
                                </div>
                                <button type="submit" class="btn btn-primary" id="kt_smart_seeder_submit">
                                    <span class="indicator-label">@lang('backend.smart_seeder.generate')</span>
                                    <span class="indicator-progress">@lang('backend.smart_seeder.generating')
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card card-flush">
                    <div class="card-header pt-6">
                        <div class="card-title">
                            <h2>@lang('backend.smart_seeder.seeders')</h2>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        @if (empty($seeders))
                            <div class="text-muted fst-italic py-10 text-center">@lang('backend.smart_seeder.no_seeders')</div>
                        @else
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                    <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-150px">@lang('backend.smart_seeder.module')</th>
                                        <th class="min-w-250px">@lang('backend.smart_seeder.instruction')</th>
                                        <th>@lang('backend.smart_seeder.records')</th>
                                        <th class="min-w-150px">@lang('backend.smart_seeder.generated_at')</th>
                                        <th class="text-end min-w-200px">@lang('backend.labels.actions')</th>
                                    </tr>
                                </thead>
                                <tbody class="fw-semibold text-gray-600">
                                    @foreach ($seeders as $seeder)
                                        <tr>
                                            <td>
                                                <div class="text-gray-800 fw-bold">{{ $seeder['meta']['module'] }}</div>
                                                <div class="text-muted fs-7">{{ $seeder['class'] }}</div>
                                            </td>
                                            <td class="mw-400px text-truncate">{{ $seeder['meta']['instruction'] ?? '' }}</td>
                                            <td>{{ $seeder['meta']['count'] ?? 0 }}</td>
                                            <td>{{ $seeder['meta']['generated_at'] ?? '—' }}</td>
                                            <td class="text-end text-nowrap">
                                                <a href="{{ route('backend.smart_seeder.show', ['seeder' => $seeder['class']]) }}"
                                                    class="btn btn-sm btn-light btn-active-light-primary">@lang('backend.buttons.view')</a>
                                                <form action="{{ route('backend.smart_seeder.run', ['seeder' => $seeder['class']]) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-light-success">@lang('backend.smart_seeder.run')</button>
                                                </form>
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

@section('scripts')
    <script>
        $('#kt_smart_seeder_form').on('submit', function() {
            $('#kt_smart_seeder_submit').attr('data-kt-indicator', 'on').prop('disabled', true);
        });
    </script>
@endsection
