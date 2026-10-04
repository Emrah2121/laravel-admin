@extends('layouts.backend.master')
@section('title', trans('backend.titles.services'))

@section('styles')
    <style>
        .image-input-placeholder {
            background-image: url('{{ asset('backend/assets/media/svg/files/blank-image.svg') }}');
        }

        [data-bs-theme="dark"] .image-input-placeholder {
            background-image: url('{{ asset('backend/assets/media/svg/files/blank-image-dark.svg') }}');
        }

        .ql-container {
            min-height: 250px;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_toolbar" class="app-toolbar pt-7 pt-lg-10">
            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                    <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                        @lang('backend.titles.services')
                    </h1>
                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ route('backend.dashboard') }}"
                                class="text-muted text-hover-primary">@lang('backend.titles.dashboard')</a>
                        </li>
                        <li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ route('backend.services.index') }}"
                                class="text-muted text-hover-primary">@lang('backend.titles.services')</a>
                        </li>
                        <li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
                        <li class="breadcrumb-item text-muted">
                            {{ $edit ? trans('backend.buttons.edit') : trans('backend.buttons.create') }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">

                <form id="kt_service_form" novalidate
                    action="{{ $edit === false ? route('backend.services.store') : route('backend.services.update', ['service' => $service->id]) }}"
                    method="POST" enctype="multipart/form-data" class="form d-flex flex-column flex-lg-row">
                    @csrf
                    @if ($edit)
                        @method('PUT')
                    @endif

                    <div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">
                        <div class="card card-flush py-4">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>@lang('backend.labels.services_image')</h2>
                                </div>
                            </div>
                            <div class="card-body text-center pt-0">
                                <div class="image-input image-input-empty image-input-outline image-input-placeholder mb-3"
                                    data-kt-image-input="true">
                                    <div class="image-input-wrapper w-150px h-150px"
                                        style="background-image: url('{{ isset($service) && $service->getDocument('service_image') ? asset($service->getDocument('service_image')) : '' }}')">
                                    </div>
                                    <label
                                        class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change image">
                                        <i class="ki-outline ki-pencil fs-7"></i>
                                        <input type="file" name="image[]" multiple="multiple"
                                            accept=".png, .jpg, .jpeg, .webp" />
                                    </label>
                                    <span
                                        class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="cancel"><i class="ki-outline ki-cross fs-2"></i></span>
                                    <span
                                        class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="remove"><i class="ki-outline ki-cross fs-2"></i></span>
                                </div>
                                @error('image')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                @error('image.*')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="card card-flush py-4">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>@lang('backend.labels.status')</h2>
                                </div>
                                <div class="card-toolbar">
                                    <div class="rounded-circle bg-success w-15px h-15px" id="kt_service_status_indicator">
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <select class="form-select mb-2" name="status" data-control="select2"
                                    data-hide-search="true" id="kt_service_status_select">
                                    <option value="1"
                                        {{ (isset($service) ? $service->status : old('status', 1)) == 1 ? 'selected' : '' }}>
                                        @lang('backend.labels.active')</option>
                                    <option value="0"
                                        {{ (isset($service) ? $service->status : old('status', 1)) == 0 ? 'selected' : '' }}>
                                        @lang('backend.labels.passive')</option>
                                </select>
                            </div>
                        </div>

                        <div class="card card-flush py-4">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>@lang('backend.labels.general')</h2>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="mb-7">
                                    <label class="required form-label">Slug</label>
                                    <input type="text" name="slug" required
                                        class="form-control mb-2 @error('slug') is-invalid @enderror"
                                        value="{{ old('slug', $service->slug ?? '') }}" />
                                    @error('slug')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-2">
                                    <label class="form-label">@lang('backend.labels.order')</label>
                                    <input type="number" name="order"
                                        class="form-control mb-2 @error('order') is-invalid @enderror"
                                        value="{{ old('order', $service->order ?? 1) }}" />
                                    @error('order')
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
                                        data-bs-toggle="tab"
                                        href="#kt_service_lang_{{ $lang->code }}">{{ $lang->name }}</a>
                                </li>
                            @endforeach
                        </ul>

                        <div class="tab-content">
                            @foreach ($langs as $lang)
                                <div class="tab-pane fade @if ($loop->first) show active @endif"
                                    id="kt_service_lang_{{ $lang->code }}" role="tabpanel">
                                    <div class="card card-flush py-4">
                                        <div class="card-header">
                                            <div class="card-title">
                                                <h2>{{ $lang->name }}</h2>
                                            </div>
                                        </div>
                                        <div class="card-body pt-0">
                                            <div class="mb-2 fv-row">
                                                <label class="required form-label">@lang('backend.labels.services_title')</label>
                                                <input type="text" name="title:{{ $lang->code }}" required maxlength="255"
                                                    class="form-control mb-2"
                                                    value="{{ old('title:' . $lang->code, isset($service) ? $service->translate($lang->code)?->title : '') }}" />
                                            </div>
                                            @error('title:' . $lang->code)
                                                <div class="text-danger">{{ $message }}</div>
                                            @enderror
                                            <div class="mb-2 fv-row">
                                                <label class="form-label">@lang('backend.labels.services_description')</label>
                                                <div class="quill-wrapper">
                                                    <div id="kt_service_description_{{ $lang->code }}" class="min-h-300px mb-2">
                                                        {!! sanitize_html(old('description:' . $lang->code, isset($service) ? $service->translate($lang->code)?->description : '')) !!}
                                                    </div>
                                                </div>
                                                <textarea name="description:{{ $lang->code }}" class="d-none"></textarea>
                                            </div>
                                            @error('description:' . $lang->code)
                                                <div class="text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if ($edit)
                            <div class="card card-flush py-4">
                                <div class="card-header">
                                    <div class="card-title">
                                        <h2>@lang('backend.labels.documents')</h2>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    @include('backend.includes.media', [
                                        'files' => $service->files,
                                        'media_collection_name' => 'service_images',
                                        'isDeleted' => true,
                                        'isCovered' => true,
                                    ])
                                </div>
                            </div>
                        @endif

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('backend.services.index') }}"
                                class="btn btn-light me-5">@lang('backend.buttons.cancel')</a>
                            <button type="submit" class="btn btn-primary" id="kt_service_submit">
                                <span class="indicator-label">@lang('backend.buttons.save')</span>
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
        $(document).ready(function() {
            const statusSelect = document.getElementById('kt_service_status_select');
            const statusIndicator = document.getElementById('kt_service_status_indicator');
            const updateStatusIndicator = () => {
                if (!statusSelect || !statusIndicator) return;
                statusIndicator.classList.remove('bg-success', 'bg-danger');
                statusIndicator.classList.add(statusSelect.value == 1 ? 'bg-success' : 'bg-danger');
            };
            $(statusSelect).on('change', updateStatusIndicator);
            updateStatusIndicator();

            const quillOptions = {
                modules: {
                    toolbar: [
                        [{
                            header: [1, 2, 3, false]
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
                theme: 'snow'
            };

            const quills = {};
            @foreach ($langs as $lang)
                quills['description:{{ $lang->code }}'] = new Quill('#kt_service_description_{{ $lang->code }}', quillOptions);
            @endforeach

            $('#kt_service_form').on('submit', function(e) {
                Object.entries(quills).forEach(([name, quill]) => {
                    let contentHtml = quill.root.innerHTML;
                    $('[name="' + name + '"]').val(contentHtml === '<p><br></p>' ? '' : contentHtml);
                });

                if (!ktValidateRequired(this)) {
                    e.preventDefault();
                    return;
                }

                $('#kt_service_submit').attr('data-kt-indicator', 'on').prop('disabled', true);
            });
        });
    </script>
@endsection
