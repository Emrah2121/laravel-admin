<button type="button" class="btn btn-sm btn-icon btn-light-primary" data-bs-toggle="collapse"
    data-bs-target="#log_details_{{ $id }}">
    <i class="ki-outline ki-plus fs-4"></i>
</button>
<div class="collapse" id="log_details_{{ $id }}">
    <textarea readonly cols="130" rows="10" class="mt-2">{{ json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</textarea>
</div>
