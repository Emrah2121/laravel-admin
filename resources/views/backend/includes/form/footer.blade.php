<div class="card-footer">
    <div class="row">
        <div class="col-lg-9 ml-lg-auto">
            <button type="submit" class="btn btn-primary me-2" id="kt_{{ $page ?? 'form' }}_submit">
                <span class="indicator-label">@lang('backend.buttons.save')</span>
                <span class="indicator-progress">@lang('backend.labels.please_wait')...
                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
            </button>
            <a href="{{ url()->previous() }}" class="btn btn-light-danger cursor-pointer">@lang('backend.buttons.back')</a>
        </div>
    </div>
</div>
