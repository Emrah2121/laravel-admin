<div class="card-body py-4">
    <table class="table align-middle table-row-dashed fs-6 gy-5" id="datatable">
        <thead>
            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                <th class="w-10px pe-2">
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input" type="checkbox" data-kt-check="true"
                            data-kt-check-target="#datatable .form-check-input" value="1" />
                    </div>
                </th>
                <th class="min-w-150px">@lang('backend.labels.name')</th>
                <th class="min-w-125px">@lang('backend.labels.guard_name')</th>
                <th class="min-w-250px">@lang('backend.labels.assigned_to')</th>
                <th class="min-w-125px">@lang('backend.labels.created_date')</th>
                <th class="min-w-100px">@lang('backend.labels.actions')</th>
            </tr>
        </thead>
        <tbody class="fw-semibold text-gray-600">
        </tbody>
    </table>
</div>
