@extends('layouts.backend.master')
@section('title', trans('backend.titles.settings'))
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
                            <a href="{{ route('backend.settings.index') }}"
                                class="text-muted text-hover-primary">@lang('backend.titles.settings')</a>
                        </li>
                        <li class="breadcrumb-item">
                            <span class="bullet bg-gray-400 w-5px h-2px"></span>
                        </li>
                        <li class="breadcrumb-item text-muted">
                            {{ $edit ? trans('backend.buttons.edit') : trans('backend.buttons.create') }}</li>
                    </ul>
                </div>
            </div>
        </div>

        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                <form id="kt_setting_form" novalidate
                    action="{{ $edit === false ? route('backend.settings.store') : route('backend.settings.update', ['setting' => $setting->id]) }}"
                    method="POST" class="form d-flex flex-column flex-lg-row">
                    @csrf
                    @if ($edit)
                        @method('PUT')
                    @endif

                    <div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">
                        <div class="card card-flush py-4">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>@lang('backend.labels.general_information')</h2>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="fv-row mb-7">
                                    <label class="required form-label">@lang('backend.labels.name')</label>
                                    <input type="text" name="name" required class="form-control mb-2"
                                        placeholder="@lang('backend.placeholders.enter.name')"
                                        value="{{ isset($setting) ? $setting->name : old('name') }}" />
                                    @error('name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="fv-row mb-7">
                                    <label class="form-label">Slug</label>
                                    <input type="text" name="slug" class="form-control mb-2" placeholder="Slug"
                                        value="{{ isset($setting) ? $setting->slug : old('slug') }}" />
                                    @error('slug')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
                        <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-n2">
                            @foreach ($langs as $lang)
                                <li class="nav-item">
                                    <a class="nav-link text-active-primary pb-4 @if ($loop->first) active @endif"
                                        data-bs-toggle="tab" href="#kt_lang_{{ $lang->code }}">
                                        {{ $lang->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        <div class="tab-content">
                            @foreach ($langs as $lang)
                                <div class="tab-pane fade @if ($loop->first) show active @endif"
                                    id="kt_lang_{{ $lang->code }}" role="tabpanel">
                                    <div class="d-flex flex-column gap-7 gap-lg-10">
                                        <div class="card card-flush py-4">
                                            <div class="card-header">
                                                <div class="card-title">
                                                    <h2>@lang('backend.labels.content') ({{ $lang->name }})</h2>
                                                </div>
                                            </div>
                                            <div class="card-body pt-0">
                                                <div>
                                                    <label class="form-label">@lang('backend.labels.content')</label>
                                                    <div id="kt_setting_content_{{ $lang->code }}"
                                                        class="min-h-200px mb-2">
                                                        {!! isset($setting) ? $setting->translate($lang->code)?->content : old('content:' . $lang->code) !!}
                                                    </div>
                                                    <textarea name="content:{{ $lang->code }}" class="d-none"></textarea>
                                                </div>
                                                @error('content:' . $lang->code)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('backend.settings.index') }}"
                                class="btn btn-light me-5">@lang('backend.buttons.cancel')</a>
                            <button type="submit" id="kt_setting_submit" class="btn btn-primary">
                                <span class="indicator-label">@lang('backend.buttons.save')</span>
                                <span class="indicator-progress">@lang('backend.labels.please_wait')
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
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
        $(document).ready(function() {
            const quills = {};
            @foreach ($langs as $lang)
                quills['content_{{ $lang->code }}'] = new Quill('#kt_setting_content_{{ $lang->code }}', {
                    modules: {
                        toolbar: [
                            [{
                                header: [1, 2, 3, 4, 5, 6, false]
                            }],
                            ['bold', 'italic', 'underline', 'strike'],
                            [{
                                'list': 'ordered'
                            }, {
                                'list': 'bullet'
                            }],
                            [{
                                'align': []
                            }],
                            ['link', 'image', 'video'],
                            ['clean']
                        ]
                    },
                    placeholder: '@lang('backend.placeholders.enter.content')',
                    theme: 'snow'
                });
            @endforeach

            $('#kt_setting_form').on('submit', function(e) {
                @foreach ($langs as $lang)
                    {
                        let contentHtml = quills['content_{{ $lang->code }}'].root.innerHTML;
                        if (contentHtml === '<p><br></p>') {
                            $('[name="content:{{ $lang->code }}"]').val('');
                        } else {
                            $('[name="content:{{ $lang->code }}"]').val(contentHtml);
                        }
                    }
                @endforeach

                if (!ktValidateRequired(this)) {
                    e.preventDefault();
                    return;
                }

                const submitButton = $('#kt_setting_submit');
                submitButton.attr('data-kt-indicator', 'on');
                submitButton.prop('disabled', true);
            });
        });
    </script>
@endsection
