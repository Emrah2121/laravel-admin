@extends('layouts.backend.master')
@section('title', trans('backend.titles.translations'))
@section('content')
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_toolbar" class="app-toolbar pt-7 pt-lg-10">
            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                    <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                        @lang('backend.titles.translations')
                    </h1>
                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ route('backend.dashboard') }}"
                                class="text-muted text-hover-primary">@lang('backend.titles.dashboard')</a>
                        </li>
                        <li class="breadcrumb-item">
                            <span class="bullet bg-gray-400 w-5px h-2px"></span>
                        </li>
                        <li class="breadcrumb-item text-muted">@lang('backend.titles.translations')</li>
                    </ul>
                </div>
            </div>
        </div>

        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div class="app-container container-xxl">
                <div class="card">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title">
                            <div class="d-flex align-items-center position-relative my-1 me-3">
                                <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                                <input type="text" id="translation_search" class="form-control form-control-solid w-250px ps-13"
                                    placeholder="@lang('backend.datatables.search')" />
                            </div>

                            <div class="w-200px my-1">
                                <select id="translation_group_filter" class="form-select form-select-solid" data-control="select2" data-placeholder="@lang('backend.labels.all_groups')">
                                    <option value="all">@lang('backend.labels.all_groups')</option>
                                    @foreach ($groups as $grp)
                                        <option value="{{ $grp }}">{{ $grp }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="card-toolbar">
                            <div class="d-flex justify-content-end gap-2">
                                <form action="{{ route('backend.translations.clear_cache') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-light-warning" title="@lang('backend.buttons.clear_cache')">
                                        <i class="ki-outline ki-arrows-circle fs-2"></i> @lang('backend.buttons.clear_cache')
                                    </button>
                                </form>

                                <form action="{{ route('backend.translations.import') }}" method="POST" class="d-inline" onsubmit="return confirm('{{ trans('backend.mixins.are_you_sure') }}');">
                                    @csrf
                                    <button type="submit" class="btn btn-light-info" title="@lang('backend.buttons.import')">
                                        <i class="ki-outline ki-file-down fs-2"></i> @lang('backend.buttons.import')
                                    </button>
                                </form>

                                @can('translations create')
                                    <a href="{{ route('backend.translations.create') }}" class="btn btn-primary">
                                        <i class="ki-outline ki-plus fs-2"></i> @lang('backend.buttons.create')
                                    </a>
                                @endcan
                            </div>
                        </div>
                    </div>

                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5" id="translations_datatable">
                                <thead>
                                    <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                        <th class="w-50px">#</th>
                                        <th class="min-w-100px">@lang('backend.labels.group')</th>
                                        <th class="min-w-200px">@lang('backend.labels.key')</th>
                                        <th class="min-w-350px">@lang('backend.titles.translations')</th>
                                        <th class="text-end min-w-100px">@lang('backend.labels.actions')</th>
                                    </tr>
                                </thead>
                                <tbody class="fw-semibold text-gray-600">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            let table = $('#translations_datatable').DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                order: [
                    [0, 'desc']
                ],
                pageLength: 15,
                ajax: {
                    url: '{{ route('backend.translations.index') }}',
                    type: 'GET',
                    data: function(d) {
                        d.search = $('#translation_search').val();
                        d.group = $('#translation_group_filter').val();
                    }
                },
                columns: [{
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'group_badge',
                        name: 'group'
                    },
                    {
                        data: 'key_formatted',
                        name: 'key'
                    },
                    {
                        data: 'translations_preview',
                        name: 'translations_preview',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-end'
                    }
                ],
                drawCallback: function() {
                    if (typeof KTMenu !== 'undefined') {
                        KTMenu.init();
                    }
                }
            });

            $('#translation_search').on('keyup', function() {
                table.draw();
            });

            $('#translation_group_filter').on('change', function() {
                table.draw();
            });

            $(document).on('click', '.btn-delete', function(e) {
                e.preventDefault();
                let btn = $(this);

                {!! confirm() !!}.then((result) => {
                    if (result.isConfirmed) {
                        let url = btn.attr('href');
                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: {
                                _method: 'DELETE',
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(res) {
                                if (res.status == 1) {
                                    {!! notify('success', trans('backend.messages.success.delete')) !!}
                                    table.draw(false);
                                } else {
                                    {!! notify('error', trans('backend.messages.error.delete')) !!}
                                }
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
