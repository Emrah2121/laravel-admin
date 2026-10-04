<div class="card-header flex-wrap py-5">
    <div class="card-title">
        @if (isset($edit) && $edit == true)
            <h3 class="card-label">{{ $page }}</h3>
        @else
            <h3 class="card-label">@lang("backend.titles.$page")</h3>
        @endif
    </div>
</div>
