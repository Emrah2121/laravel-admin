<script>
    let columnsArr = {!! json_encode($columns) !!},
        lang = '{{ config('app.locale') }}',
        pageLength = {{ isset($pageLength) ? json_encode($pageLength) : 10 }},
        columns = columnsArr.map(item => {
            return {
                data: item,
                name: item,
            };
        });

    lang === 'az' ?
        langUrl = '{{ asset('/backend/js/datatables/langs/az_az.json') }}' :
        langUrl = '';

    let table = $('#datatable').DataTable({
        oLanguage: {
            url: langUrl
        },
        ajax: {
            url: '{{ $route }}',
            type: 'GET',
            data: function(d) {
                d.search = $('[data-kt-user-table-filter="search"]').val();
                d.status = $('select[name=status]').val();
            },
        },
        serverSide: true,
        processing: true,
        searching: false,
        scrollCollapse: true,
        aaSorting: [
            [0, false]
        ],
        order: {!! isset($order) ? json_encode($order) : '[[0, "desc"]]' !!},
        buttons: [{
                extend: 'colvis',
            },
            {
                extend: 'csv',
                charset: 'UTF-8',
                fieldSeparator: ';',
                bom: true,
            },
            {
                extend: 'excel',
                charset: 'UTF-8',
                bom: true,
            },
            {
                extend: 'pdf',
                charset: 'UTF-8',
                bom: true,
            },
            {
                extend: 'print',
                charset: 'UTF-8',
                bom: true,
                exportOptions: {
                    stripHtml: false,
                }
            },
        ],
        "pageLength": pageLength,
        "lengthChange": false,
        columns,
        autoWidth: false,
        columnDefs: [{
                targets: 0,
                orderable: false,
                className: 'no-sort',
            },
            {
                targets: columns.length - 1,
                orderable: false,
                className: 'no-sort',
            },
        ],
        ordering: true,
        dom: "<'table-responsive'tr>" +
            "<'row'" +
            "<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'i>" +
            "<'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>" +
            ">",
    }).on("draw", function() {
        if (typeof KTMenu !== 'undefined') {
            KTMenu.init();
        }
    });

    $(document).on('click', '[data-export]', function(e) {
        e.preventDefault();
        let type = $(this).data('export');
        table.button('.buttons-' + type).trigger();
    });

    table.columns().every(function() {
        var column = this;
        var title = $(column.header()).text().trim();
        if (title && title !== "" && !$(column.header()).find('input[type="checkbox"]').length) {
            var selected = column.visible() ? 'selected' : '';
            $('#colvis_select').append(
                '<option value="' + column.index() + '" ' + selected + '>' + title + '</option>'
            );
        }
    });

    var $colvisSelect = $('#colvis_select');
    var $menu = $colvisSelect.closest('.menu-sub-dropdown');

    $colvisSelect.select2({
        closeOnSelect: false,
        placeholder: "Select columns",
        allowClear: true,
        dropdownParent: $menu
    });

    $colvisSelect.parent().on('click', function(e) {
        e.stopPropagation();
    });

    $('#colvis_select').on('change', function(e) {
        var selectedCols = $(this).val();

        $('#colvis_select option').each(function() {
            var colIdx = $(this).val();
            var column = table.column(colIdx);

            if (selectedCols && selectedCols.includes(colIdx)) {
                column.visible(true);
            } else {
                column.visible(false);
            }
        });
    });

    $('[data-kt-user-table-filter="search"]').keyup(function() {
        table.draw();
    });

    $('.filter-select').on('change', function() {
        table.search($(this).val())
            .draw();
    });

    $(document).on('click', '.btn-delete', function(e) {
        e.preventDefault();
        let btn = $(this);

        {!! confirm() !!}.then((result) => {
            if (result.isConfirmed) {
                var url = btn.prop('href');
                var type = 'POST';
                var data = {
                    _method: 'DELETE',
                    _token: '{{ csrf_token() }}'
                };

                $.ajax({
                    url: url,
                    type: type,
                    data: data,
                    success: function(result) {
                        if (result.status == 1) {
                            {!! notify('success', trans('backend.messages.success.delete')) !!}
                            table.row(btn.parents('tr')).remove().draw();
                        } else {
                            {!! notify('error', trans('backend.messages.error.delete')) !!}
                        }
                    }
                });
            }
        });
    });

    function toggleBulkToolbar() {
        var checked = $('#datatable tbody .form-check-input:checked').length;
        var toolbarBase = $('[data-kt-user-table-toolbar="base"]');
        var toolbarSelected = $('[data-kt-user-table-toolbar="selected"]');

        if (checked > 0) {
            $('[data-kt-user-table-select="selected_count"]').text(checked);
            toolbarBase.addClass('d-none');
            toolbarSelected.removeClass('d-none');
        } else {
            toolbarBase.removeClass('d-none');
            toolbarSelected.addClass('d-none');
        }
    }

    $(document).on('change', '#datatable .form-check-input', function() {
        setTimeout(toggleBulkToolbar, 50);
    });

    $(document).on('click', '[data-kt-user-table-select="delete_selected"]', function(e) {
        e.preventDefault();

        var ids = $('#datatable tbody .form-check-input:checked').map(function() {
            return $(this).val();
        }).get();

        if (!ids.length) {
            return;
        }

        {!! confirm() !!}.then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            var requests = ids.map(function(id) {
                return $.ajax({
                    url: '{{ $route }}/' + id,
                    type: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token: '{{ csrf_token() }}'
                    }
                });
            });

            Promise.allSettled(requests).then(function(results) {
                var failed = results.some(function(r) {
                    return r.status !== 'fulfilled' || r.value?.status != 1;
                });

                if (failed) {
                    {!! notify('error', trans('backend.messages.error.delete')) !!}
                } else {
                    {!! notify('success', trans('backend.messages.success.delete')) !!}
                }

                $('[data-kt-check]').prop('checked', false);
                $('[data-kt-user-table-select="selected_count"]').text(0);
                $('[data-kt-user-table-toolbar="selected"]').addClass('d-none');
                $('[data-kt-user-table-toolbar="base"]').removeClass('d-none');
                table.ajax.reload(null, false);
            });
        });
    });
</script>
<style>
    #datatable thead th.no-sort:after,
    #datatable thead th.no-sort:before {
        display: none !important;
    }

    #datatable thead th,
    #datatable tbody td {
        white-space: nowrap;
        padding-left: 0.75rem !important;
        padding-right: 0.75rem !important;
    }
</style>
