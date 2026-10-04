<div class="card-toolbar">
    @can("$page delete")
        <div class="d-flex justify-content-end align-items-center d-none" data-kt-user-table-toolbar="selected">
            <div class="fw-bold me-5">
                <span class="me-2" data-kt-user-table-select="selected_count">0</span>@lang('backend.labels.selected')
            </div>
            <button type="button" class="btn btn-danger" data-kt-user-table-select="delete_selected">
                <i class="ki-outline ki-trash fs-2"></i> @lang('backend.buttons.delete_selected')
            </button>
        </div>
    @endcan
    <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
        <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                data-kt-menu-placement="bottom-end">
            <i class="ki-outline ki-filter fs-2"></i> @lang('backend.buttons.filter')
        </button>
        <div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px" data-kt-menu="true">
            <div class="px-7 py-5">
                <div class="fs-5 text-dark fw-bold">@lang('backend.buttons.filter')</div>
            </div>
            <div class="separator border-gray-200"></div>
            <div class="px-7 py-5">
                <div class="mb-10">
                    <label class="form-label fw-semibold">@lang('backend.buttons.columns')</label>
                    <select class="form-select form-select-solid" data-close-on-select="false"
                            data-placeholder="@lang('backend.buttons.select_columns')" data-allow-clear="true" multiple="multiple"
                            id="colvis_select" style="width: 100%">
                    </select>
                </div>
            </div>
        </div>

        <button type="button" class="btn btn-light-primary me-3" data-bs-toggle="modal"
                data-bs-target="#kt_modal_export">
            <i class="ki-outline ki-exit-up fs-2"></i> @lang('backend.buttons.export')
        </button>
        <div class="modal fade" id="kt_modal_export" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered mw-400px">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="fw-bold">@lang('backend.titles.export')</h2>
                        <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </div>
                    </div>
                    <div class="modal-body scroll-y mx-5 my-7">
                        <div class="d-flex flex-column gap-3">
                            <button type="button" class="btn btn-light-primary w-100" data-export="csv"
                                    data-bs-dismiss="modal">CSV</button>
                            <button type="button" class="btn btn-light-success w-100" data-export="excel"
                                    data-bs-dismiss="modal">Excel</button>
                            <button type="button" class="btn btn-light-danger w-100" data-export="pdf"
                                    data-bs-dismiss="modal">PDF</button>
                            <button type="button" class="btn btn-light-warning w-100" data-export="print"
                                    data-bs-dismiss="modal">Print</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if (!isset($hideCreate) || !$hideCreate)
            @if (isset($modal) && $modal)
                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#{{ $modal_id ?? 'kt_modal_add_' . $page }}">
                    <i class="ki-outline ki-plus fs-2"></i> @lang('backend.buttons.create')
                </button>
            @else
                <a href="{{ route("backend.$page.create") }}" class="btn btn-primary">
                    <i class="ki-outline ki-plus fs-2"></i> @lang('backend.buttons.create')
                </a>
            @endif
        @endif
    </div>
</div>
