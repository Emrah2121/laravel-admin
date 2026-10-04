@extends('layouts.backend.master')
@section('title', trans('backend.titles.crud_generator'))

@section('styles')
    <style>
        .crud-diff {
            font-family: SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 0.85rem;
            white-space: pre;
            overflow-x: auto;
        }

        .crud-diff .line-number {
            display: inline-block;
            width: 3.5rem;
            color: var(--bs-gray-500);
            user-select: none;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_toolbar" class="app-toolbar pt-7 pt-lg-10">
            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                    <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                        @lang('backend.crud_generator.preview'): {{ $spec->name }}
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
                        <li class="breadcrumb-item text-muted">@lang('backend.crud_generator.preview')</li>
                    </ul>
                </div>
            </div>
        </div>

        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl d-flex flex-column gap-7 gap-lg-10">
                <div class="card card-flush py-4">
                    <div class="card-header">
                        <div class="card-title">
                            <h2>@lang('backend.crud_generator.derived')</h2>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <table class="table table-row-dashed fs-6 gy-3 mb-0">
                            <tbody>
                                @foreach ($names as $title => $value)
                                    <tr>
                                        <td class="fw-bold text-gray-800 w-150px">{{ $title }}</td>
                                        <td><code>{{ $value }}</code></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card card-flush py-4">
                    <div class="card-header">
                        <div class="card-title">
                            <h2>@lang('backend.crud_generator.files_created') ({{ count($created) }})</h2>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <ul class="mb-0">
                            @foreach ($created as $path)
                                <li><code>{{ $path }}</code></li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="card card-flush py-4">
                    <div class="card-header">
                        <div class="card-title">
                            <h2>@lang('backend.crud_generator.files_modified') ({{ count($modified) }})</h2>
                        </div>
                    </div>
                    <div class="card-body pt-0 d-flex flex-column gap-5">
                        @foreach ($modified as $path => $diffs)
                            <div>
                                <div class="fw-bold text-gray-800 mb-2"><code>{{ $path }}</code></div>
                                @foreach ($diffs as $diff)
                                    <div class="crud-diff border rounded p-3 mb-2">@foreach ($diff as $line)<div class="{{ $line['type'] === 'add' ? 'bg-light-success text-success' : 'text-gray-600' }}"><span class="line-number">{{ $line['line'] }}</span>{{ $line['type'] === 'add' ? '+' : ' ' }} {{ $line['text'] }}</div>@endforeach</div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>

                <form action="{{ route('backend.crud_generator.store') }}" method="POST" class="card card-flush py-4">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}" />

                    <div class="card-body d-flex flex-wrap align-items-center gap-10">
                        <label class="form-check form-check-custom form-check-solid">
                            <input type="hidden" name="migrate" value="0" />
                            <input class="form-check-input" type="checkbox" name="migrate" value="1" @disabled(!$artisanAllowed) />
                            <span class="form-check-label">@lang('backend.crud_generator.run_migrations')</span>
                        </label>
                        <label class="form-check form-check-custom form-check-solid">
                            <input type="hidden" name="seed" value="0" />
                            <input class="form-check-input" type="checkbox" name="seed" value="1" @disabled(!$artisanAllowed) />
                            <span class="form-check-label">@lang('backend.crud_generator.run_seeder')</span>
                        </label>
                        @if (!$artisanAllowed)
                            <span class="text-muted fs-7">@lang('backend.crud_generator.artisan_disabled')</span>
                        @endif

                        <div class="ms-auto">
                            <button type="button" class="btn btn-light me-3" onclick="history.back()">@lang('backend.buttons.back')</button>
                            <button type="submit" class="btn btn-primary" id="kt_crud_generator_confirm">
                                <span class="indicator-label">@lang('backend.crud_generator.confirm')</span>
                                <span class="indicator-progress">@lang('backend.labels.please_wait')...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $('#kt_crud_generator_confirm').closest('form').on('submit', function() {
            $('#kt_crud_generator_confirm').attr('data-kt-indicator', 'on').prop('disabled', true);
        });
    </script>
@endsection
