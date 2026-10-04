@if ($files->count() > 0)
    <div class="card card-flush py-4 mt-4 border border-primary border-dashed">
        <div class="card-header">
            <div class="card-title">
                <h2>@lang('backend.labels.images')</h2>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="d-flex flex-wrap gap-5">
                @foreach ($files as $image)
                    <div class="mb-2" id="media-item-{{ $image->id }}">
                        <div class="symbol symbol-100px symbol-lg-150px overlay overflow-hidden border">
                            <div class="symbol-label" style="background-image:url('{{ $image->document }}')"></div>

                            <div class="overlay-layer bg-dark bg-opacity-25 shadow">
                                <div class="d-flex flex-wrap justify-content-center gap-2">
                                    <a href="{{ $image->document }}"
                                        data-fslightbox="gallery-{{ str_replace(['-', ' ', '.'], '_', $media_collection_name) }}"
                                        data-type="image"
                                        class="btn btn-icon btn-sm btn-active-color-primary btn-color-white bg-white bg-opacity-25 bgi-blur"
                                        title="@lang('backend.buttons.view')">
                                        <i class="ki-outline ki-eye fs-2"></i>
                                    </a>

                                    @if (isset($isDeleted) && $isDeleted)
                                        <button type="button" data-id="{{ $image->id }}"
                                            class="btn btn-icon btn-sm btn-active-color-danger btn-color-white bg-white bg-opacity-25 bgi-blur dodelete"
                                            title="@lang('backend.buttons.delete')">
                                            <i class="ki-outline ki-trash fs-2"></i>
                                        </button>
                                    @endif

                                    @if (isset($isCovered) && $isCovered && $image->status != 'main')
                                        <button type="button" data-id="{{ $image->id }}"
                                            class="btn btn-icon btn-sm btn-active-color-success btn-color-white bg-white bg-opacity-25 bgi-blur docover"
                                            title="@lang('backend.labels.set_as_cover')">
                                            <i class="ki-outline ki-star fs-2"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            @if ($image->status == 'main')
                                <span class="badge badge-success position-absolute top-0 end-0 m-2 shadow-sm">Cover</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @push('extrascripts')
        <script src="{{ asset('backend/assets/plugins/custom/fslightbox/fslightbox.bundle.js') }}"></script>
        <script>
            $(document).ready(function() {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    }
                });

                $('.docover').click(function(e) {
                    e.preventDefault();
                    let id = $(this).data('id');
                    {!! confirm_update() !!}.then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                type: 'post',
                                url: '/gload/documents/' + id + '/set-status',
                                data: {
                                    'id': id
                                },
                                dataType: 'json',
                                success: function(result) {
                                    if (result.status != 1) {
                                        {!! notify('error', trans('backend.messages.error.cover')) !!}
                                    } else {
                                        {!! notify('success', trans('backend.messages.success.cover')) !!}
                                    }
                                    location.reload();
                                },
                            });
                        }
                    });
                });

                $('.dodelete').click(function(e) {
                    e.preventDefault();
                    let id = $(this).data('id');
                    {!! confirm() !!}.then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                type: 'post',
                                url: '/gload/documents/' + id + '/delete',
                                data: {
                                    'id': id
                                },
                                dataType: 'json',
                                success: function(result) {
                                    if (result.status != 1) {
                                        {!! notify('error', trans('backend.messages.error.delete')) !!}
                                    } else {
                                        {!! notify('success', trans('backend.messages.success.delete')) !!}
                                        $('#media-item-' + id).remove();
                                        refreshFsLightbox();
                                    }
                                }
                            });
                        }
                    });
                });
            });
        </script>
    @endpush
@endif
