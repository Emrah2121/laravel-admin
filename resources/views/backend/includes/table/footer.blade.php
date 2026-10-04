<div class="card-footer">
    <div class="row">
        <div class="mx-auto">
            @can("$page edit")
                <a href="{{ route("backend.$page.edit", $id) }}" class="btn btn-success mr-2">
                    @lang('backend.buttons.update')
                </a>
            @endcan
            <a href="{{ url()->previous() }}" class="btn btn-danger">
                @lang('backend.buttons.back')
            </a>
        </div>
    </div>
</div>
