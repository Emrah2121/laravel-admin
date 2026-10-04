@extends('layouts.backend.master')
@section('title', trans('backend.titles.smart_seeder'))

@section('content')
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_toolbar" class="app-toolbar pt-7 pt-lg-10">
            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                    <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                        {{ $data['class'] }}
                    </h1>
                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ route('backend.dashboard') }}" class="text-muted text-hover-primary">@lang('backend.titles.dashboard')</a>
                        </li>
                        <li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ route('backend.smart_seeder.index') }}" class="text-muted text-hover-primary">@lang('backend.titles.smart_seeder')</a>
                        </li>
                        <li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
                        <li class="breadcrumb-item text-muted">{{ $data['meta']['module'] }}</li>
                    </ul>
                </div>

                <div class="d-flex align-items-center gap-2 gap-lg-3">
                    <form action="{{ route('backend.smart_seeder.run', ['seeder' => $data['class']]) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm fw-bold btn-success">@lang('backend.smart_seeder.run')</button>
                    </form>
                    <a href="{{ route('backend.smart_seeder.index') }}" class="btn btn-sm fw-bold btn-secondary">@lang('backend.buttons.back')</a>
                </div>
            </div>
        </div>

        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl d-flex flex-column gap-7 gap-lg-10">
                @if (!empty($warnings))
                    <div class="alert alert-warning mb-0">
                        <div class="fw-bold mb-2">@lang('backend.smart_seeder.warnings')</div>
                        @foreach ($warnings as $warning)
                            <div>{{ $warning }}</div>
                        @endforeach
                    </div>
                @endif

                <div class="card card-flush py-4">
                    <div class="card-body">
                        <div class="row g-5 fs-6">
                            <div class="col-md-3">
                                <div class="fw-bold text-gray-800">@lang('backend.smart_seeder.module')</div>
                                <div class="text-gray-600">{{ $data['meta']['module'] }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="fw-bold text-gray-800">@lang('backend.smart_seeder.records')</div>
                                <div class="text-gray-600">{{ $data['meta']['count'] }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="fw-bold text-gray-800">@lang('backend.smart_seeder.gemini_model')</div>
                                <div class="text-gray-600">{{ $data['meta']['ai_model'] ?? '—' }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="fw-bold text-gray-800">@lang('backend.smart_seeder.generated_at')</div>
                                <div class="text-gray-600">{{ $data['meta']['generated_at'] ?? '—' }}</div>
                            </div>
                            <div class="col-12">
                                <div class="fw-bold text-gray-800">@lang('backend.smart_seeder.instruction')</div>
                                <div class="text-gray-600">{{ $data['meta']['instruction'] }}</div>
                            </div>
                            <div class="col-12">
                                <div class="fw-bold text-gray-800">@lang('backend.smart_seeder.artisan_hint')</div>
                                <code>php artisan db:seed --class="Database\Seeders\Smart\{{ $data['class'] }}"</code>
                                <div class="text-muted fs-7 mt-2">@lang('backend.smart_seeder.run_hint')</div>
                            </div>
                        </div>
                    </div>
                </div>

                @foreach ($data['records'] as $index => $record)
                    <div class="card card-flush py-4">
                        <div class="card-header">
                            <div class="card-title">
                                <h3>#{{ $index + 1 }} {{ $record['main']['slug'] ?? '' }}</h3>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="row g-7">
                                <div class="col-lg-4">
                                    @foreach ($record['images'] ?? [] as $field => $paths)
                                        @foreach ($paths as $path)
                                            <div class="mb-3">
                                                <img src="{{ route('backend.smart_seeder.asset', ['seeder' => $data['class'], 'file' => basename($path)]) }}"
                                                    class="rounded w-100 mb-1" alt="{{ $field }}" loading="lazy" />
                                                @if (isset($credits[$path]))
                                                    <div class="text-muted fs-8">
                                                        @lang('backend.smart_seeder.photo_by')
                                                        <a href="{{ $credits[$path]['photographer_url'] }}" target="_blank" rel="noopener">{{ $credits[$path]['photographer'] }}</a>
                                                        · <a href="{{ $credits[$path]['url'] }}" target="_blank" rel="noopener">Pexels</a>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    @endforeach

                                    <div class="fw-bold text-gray-800 mb-2">@lang('backend.smart_seeder.main_fields')</div>
                                    @foreach ($record['main'] as $field => $value)
                                        <div class="mb-2">
                                            <span class="text-muted">{{ $field }}:</span>
                                            <span class="text-gray-800">{!! is_bool($value) ? badge($value) : sanitize_html((string) $value) !!}</span>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="col-lg-8">
                                    <ul class="nav nav-tabs nav-line-tabs mb-5 fs-6">
                                        @foreach ($langs as $lang)
                                            <li class="nav-item">
                                                <a class="nav-link @if ($loop->first) active @endif" data-bs-toggle="tab"
                                                    href="#kt_smart_record_{{ $index }}_{{ $lang->code }}">{{ $lang->name }}</a>
                                            </li>
                                        @endforeach
                                    </ul>
                                    <div class="tab-content">
                                        @foreach ($langs as $lang)
                                            <div class="tab-pane fade @if ($loop->first) show active @endif" id="kt_smart_record_{{ $index }}_{{ $lang->code }}">
                                                @foreach ($record['translations'][$lang->code] ?? [] as $field => $value)
                                                    <div class="mb-5">
                                                        <div class="text-muted fs-7 mb-1">{{ $field }}</div>
                                                        <div class="text-gray-800">{!! sanitize_html((string) $value) !!}</div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
