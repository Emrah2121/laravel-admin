@extends('layouts.backend.master')
@section('title', trans('backend.titles.crud_generator'))

@section('content')
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_toolbar" class="app-toolbar pt-7 pt-lg-10">
            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                    <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                        @lang('backend.crud_generator.new_module')
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
                        <li class="breadcrumb-item text-muted">@lang('backend.buttons.create')</li>
                    </ul>
                </div>
            </div>
        </div>

        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                <form id="kt_crud_generator_form" action="{{ route('backend.crud_generator.preview') }}" method="POST"
                    class="d-flex flex-column gap-7 gap-lg-10">
                    @csrf

                    @if ($errors->any())
                        <div class="alert alert-danger mb-0">
                            @foreach ($errors->all() as $message)
                                <div>{{ $message }}</div>
                            @endforeach
                        </div>
                    @endif

                    <div class="card card-flush py-4">
                        <div class="card-header">
                            <div class="card-title">
                                <h2>@lang('backend.crud_generator.module')</h2>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="row g-7">
                                <div class="col-md-3">
                                    <label class="required form-label">@lang('backend.crud_generator.name')</label>
                                    <input type="text" name="name" required value="{{ old('name') }}" placeholder="Product"
                                        class="form-control @error('name') is-invalid @enderror" data-module-name />
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">@lang('backend.crud_generator.plural')</label>
                                    <input type="text" name="plural" value="{{ old('plural') }}" data-module-plural
                                        class="form-control @error('plural') is-invalid @enderror" />
                                    @error('plural')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">@lang('backend.crud_generator.label')</label>
                                    <input type="text" name="label" value="{{ old('label') }}" data-module-label
                                        class="form-control @error('label') is-invalid @enderror" />
                                    @error('label')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">@lang('backend.crud_generator.icon')</label>
                                    <input type="text" name="icon" value="{{ old('icon') }}" placeholder="element-11"
                                        class="form-control @error('icon') is-invalid @enderror" />
                                    @error('icon')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="text-muted fs-7 mt-3">@lang('backend.crud_generator.name_hint')</div>

                            <div class="separator my-7"></div>

                            <h4 class="mb-4">@lang('backend.crud_generator.derived')</h4>
                            <div class="row g-3 fs-7 text-gray-700" id="kt_crud_generator_derived">
                                <div class="col-md-4"><span class="fw-bold">Model:</span> <code data-derived="class"></code></div>
                                <div class="col-md-4"><span class="fw-bold">Table:</span> <code data-derived="table"></code></div>
                                <div class="col-md-4"><span class="fw-bold">Route:</span> <code data-derived="route"></code></div>
                                <div class="col-md-4"><span class="fw-bold">Controller:</span> <code data-derived="controller"></code></div>
                                <div class="col-md-4"><span class="fw-bold">Translations:</span> <code data-derived="translation_table"></code></div>
                                <div class="col-md-4"><span class="fw-bold">Permissions:</span> <code data-derived="permissions"></code></div>
                            </div>

                            <div class="separator my-7"></div>

                            <h4 class="mb-4">@lang('backend.crud_generator.base_options')</h4>
                            <div class="d-flex flex-wrap gap-10">
                                <label class="form-check form-check-custom form-check-solid">
                                    <input type="hidden" name="slug" value="0" />
                                    <input class="form-check-input" type="checkbox" name="slug" value="1" @checked(old('slug', 1)) />
                                    <span class="form-check-label">Slug</span>
                                </label>
                                <label class="form-check form-check-custom form-check-solid">
                                    <input type="hidden" name="publish_date" value="0" />
                                    <input class="form-check-input" type="checkbox" name="publish_date" value="1" @checked(old('publish_date', 1)) />
                                    <span class="form-check-label">@lang('backend.labels.date')</span>
                                </label>
                                <label class="form-check form-check-custom form-check-solid">
                                    <input type="hidden" name="order" value="0" />
                                    <input class="form-check-input" type="checkbox" name="order" value="1" @checked(old('order', 1)) />
                                    <span class="form-check-label">@lang('backend.labels.order')</span>
                                </label>
                                <label class="form-check form-check-custom form-check-solid">
                                    <input type="hidden" name="status" value="0" />
                                    <input class="form-check-input" type="checkbox" name="status" value="1" @checked(old('status', 1)) />
                                    <span class="form-check-label">@lang('backend.labels.status')</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="card card-flush py-4">
                        <div class="card-header">
                            <div class="card-title">
                                <h2>@lang('backend.crud_generator.fields')</h2>
                            </div>
                            <div class="card-toolbar">
                                <button type="button" class="btn btn-sm btn-light-primary" id="kt_crud_generator_add_field">
                                    <i class="ki-outline ki-plus fs-2"></i> @lang('backend.crud_generator.add_field')
                                </button>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed fs-6 gy-3">
                                    <thead>
                                        <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                            <th class="min-w-150px">@lang('backend.crud_generator.field_name')</th>
                                            <th class="min-w-150px">@lang('backend.crud_generator.field_label')</th>
                                            <th class="min-w-125px">@lang('backend.crud_generator.type')</th>
                                            <th>@lang('backend.crud_generator.max_length')</th>
                                            <th class="text-center">@lang('backend.crud_generator.required')</th>
                                            <th class="text-center">@lang('backend.crud_generator.translatable')</th>
                                            <th class="text-center">@lang('backend.crud_generator.quill')</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="kt_crud_generator_fields">
                                        @foreach ($fields as $index => $field)
                                            @include('backend.crud_generator.partials.field', ['index' => $index, 'field' => $field])
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="text-muted fs-7">@lang('backend.crud_generator.field_hint')</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="{{ route('backend.crud_generator.index') }}" class="btn btn-light me-5">@lang('backend.buttons.cancel')</a>
                        <button type="submit" class="btn btn-primary">@lang('backend.crud_generator.preview')</button>
                    </div>
                </form>

                <template id="kt_crud_generator_field_template">
                    @include('backend.crud_generator.partials.field', [
                        'index' => '__INDEX__',
                        'field' => ['type' => 'string', 'required' => 0, 'translatable' => 1, 'quill' => 0, 'max_length' => 255],
                    ])
                </template>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            const $fields = $('#kt_crud_generator_fields');

            const studly = (value) => value.replace(/[^A-Za-z0-9]+/g, ' ').trim().split(' ')
                .filter(Boolean).map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join('');
            const snake = (value) => value.replace(/([a-z0-9])([A-Z])/g, '$1_$2').toLowerCase();
            const plural = (value) => {
                if (/[^aeiou]y$/i.test(value)) return value.slice(0, -1) + 'ies';
                if (/(s|x|z|ch|sh)$/i.test(value)) return value + 'es';
                return value + 's';
            };

            // Preview only: the server (Str::plural) decides the final names.
            const updateDerived = () => {
                const name = studly($('[data-module-name]').val() || '');
                const pluralName = studly($('[data-module-plural]').val() || '') || plural(name);
                const key = snake(pluralName);

                $('[data-derived="class"]').text(name ? 'App\\Models\\' + name : '');
                $('[data-derived="controller"]').text(name ? name + 'Controller' : '');
                $('[data-derived="table"]').text(name ? key : '');
                $('[data-derived="translation_table"]').text(name ? snake(name) + '_translations' : '');
                $('[data-derived="route"]').text(name ? 'backend.' + key + '.*' : '');
                $('[data-derived="permissions"]').text(name ? key + ' index|create|show|edit|delete' : '');
            };

            const syncRow = (row) => {
                const type = $(row).find('[data-field-type]').val();

                $(row).find('[data-field-max-length]').toggleClass('d-none', type !== 'string');
                $(row).find('[data-field-quill]').toggleClass('d-none', type !== 'textarea');
                $(row).find('[data-field-translatable]').toggleClass('d-none', type === 'image');

                if (type !== 'textarea') {
                    $(row).find('[data-field-quill]').prop('checked', false);
                }

                if (type === 'image') {
                    $(row).find('[data-field-translatable]').prop('checked', false);
                }
            };

            // Inputs are named fields[i][...]; keep i equal to the row position so
            // the submitted order and the server's error keys match the table.
            const renumber = () => {
                $fields.find('[data-field-row]').each(function(index) {
                    $(this).find('[name^="fields["]').each(function() {
                        this.name = this.name.replace(/^fields\[[^\]]+\]/, 'fields[' + index + ']');
                    });
                });
            };

            $('#kt_crud_generator_add_field').on('click', function() {
                const template = document.getElementById('kt_crud_generator_field_template').innerHTML;
                const $row = $(template.replace(/__INDEX__/g, $fields.children().length));

                $fields.append($row);
                syncRow($row);
                renumber();
            });

            $fields.on('click', '[data-field-remove]', function() {
                if ($fields.children().length > 1) {
                    $(this).closest('tr').remove();
                    renumber();
                }
            });

            $fields.on('click', '[data-field-up]', function() {
                const $row = $(this).closest('tr');
                $row.prev().before($row);
                renumber();
            });

            $fields.on('click', '[data-field-down]', function() {
                const $row = $(this).closest('tr');
                $row.next().after($row);
                renumber();
            });

            $fields.on('change', '[data-field-type]', function() {
                syncRow($(this).closest('tr'));
            });

            $('[data-module-name], [data-module-plural]').on('input', updateDerived);

            $fields.find('[data-field-row]').each(function() {
                syncRow(this);
            });
            updateDerived();
        });
    </script>
@endsection
