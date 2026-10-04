@extends('layouts.backend.master')
@section('title', $edit ? trans('backend.buttons.edit') : trans('backend.buttons.create'))
@section('content')
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_toolbar" class="app-toolbar pt-7 pt-lg-10">
            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                    <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                        {{ $edit ? trans('backend.buttons.edit') : trans('backend.buttons.create') }}
                    </h1>
                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ route('backend.dashboard') }}"
                                class="text-muted text-hover-primary">@lang('backend.titles.dashboard')</a>
                        </li>
                        <li class="breadcrumb-item">
                            <span class="bullet bg-gray-400 w-5px h-2px"></span>
                        </li>
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ route('backend.translations.index') }}"
                                class="text-muted text-hover-primary">@lang('backend.titles.translations')</a>
                        </li>
                        <li class="breadcrumb-item">
                            <span class="bullet bg-gray-400 w-5px h-2px"></span>
                        </li>
                        <li class="breadcrumb-item text-muted">
                            {{ $edit ? trans('backend.buttons.edit') : trans('backend.buttons.create') }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div class="app-container container-xxl">
                <form id="kt_translation_form" novalidate
                    action="{{ $edit ? route('backend.translations.update', ['translation' => $translation->id]) : route('backend.translations.store') }}"
                    method="POST" class="form d-flex flex-column flex-lg-row">
                    @csrf
                    @if ($edit)
                        @method('PUT')
                    @endif

                    <div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-350px mb-7 me-lg-10">
                        <div class="card card-flush py-4">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>@lang('backend.labels.general_information')</h2>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="mb-7">
                                    <label class="required form-label fw-semibold">@lang('backend.labels.group')</label>
                                    <input type="text" name="group" class="form-control mb-2" list="group_options"
                                        value="{{ old('group', $edit ? $translation->group : '*') }}" required />
                                    <datalist id="group_options">
                                        @foreach ($groups as $grp)
                                            <option value="{{ $grp }}">{{ $grp }}</option>
                                        @endforeach
                                    </datalist>
                                    @error('group')
                                        <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-7">
                                    <label class="required form-label fw-semibold">@lang('backend.labels.key')</label>
                                    <textarea name="key" class="form-control mb-2" rows="2" required>{{ old('key', $edit ? $translation->key : '') }}</textarea>
                                    @error('key')
                                        <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
                        <div class="card card-flush py-4">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>@lang('backend.titles.translations')</h2>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-5 fw-semibold mb-8">
                                    @foreach ($languages as $index => $lang)
                                        <li class="nav-item">
                                            <a class="nav-link text-active-primary pb-4 {{ $index === 0 ? 'active' : '' }}"
                                                data-bs-toggle="tab" href="#kt_translation_lang_{{ $lang->code }}">
                                                <span class="badge badge-light-primary me-2 text-uppercase">{{ $lang->code }}</span>
                                                {{ $lang->name }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>

                                <div class="tab-content">
                                    @foreach ($languages as $index => $lang)
                                        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
                                            id="kt_translation_lang_{{ $lang->code }}" role="tabpanel">
                                            <div class="mb-5">
                                                <label class="form-label fw-semibold fs-6">
                                                    {{ $lang->name }} ({{ strtoupper($lang->code) }})
                                                </label>
                                                <textarea name="value[{{ $lang->code }}]" class="form-control form-control-solid"
                                                    rows="4">{{ old("value.{$lang->code}", $edit ? ($translation->value[$lang->code] ?? '') : '') }}</textarea>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-3">
                            <a href="{{ route('backend.translations.index') }}" class="btn btn-light">
                                @lang('backend.buttons.cancel')
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ki-outline ki-check fs-2"></i> @lang('backend.buttons.save')
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
        $('#kt_translation_form').on('submit', function(e) {
            if (!ktValidateRequired(this)) {
                e.preventDefault();
            }
        });
    </script>
@endsection
