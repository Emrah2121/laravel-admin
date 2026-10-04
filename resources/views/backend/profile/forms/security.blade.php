<form action="{{ route('backend.profile.security') }}" method="POST" id="kt_security_form" novalidate
    class="form d-flex flex-column gap-7 gap-lg-10" autocomplete="off">
    @csrf
    @method('PATCH')

    <div class="card card-flush py-4">
        <div class="card-header">
            <div class="card-title">
                <h2>@lang('backend.labels.rate_limit')</h2>
            </div>
            <div class="card-toolbar">
                <div class="rounded-circle w-15px h-15px {{ old('rate_limit_status', $security->rate_limit_status) == 1 ? 'bg-success' : 'bg-danger' }}"
                    id="kt_rate_limit_status_indicator"></div>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="fv-row">
                <label class="form-label">@lang('backend.labels.status')</label>
                <select name="rate_limit_status" class="form-select mb-2 js-status-select @error('rate_limit_status') is-invalid @enderror"
                    data-control="select2" data-hide-search="true" data-indicator="#kt_rate_limit_status_indicator">
                    <option value="1"
                        {{ old('rate_limit_status', $security->rate_limit_status) == 1 ? 'selected' : '' }}>
                        @lang('backend.mixins.active')</option>
                    <option value="0"
                        {{ old('rate_limit_status', $security->rate_limit_status) == 0 ? 'selected' : '' }}>
                        @lang('backend.mixins.passive')</option>
                </select>
                @error('rate_limit_status')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <div class="text-muted fs-7">@lang('backend.notes.rate_limit')</div>
            </div>
        </div>
    </div>

    <div class="card card-flush py-4">
        <div class="card-header">
            <div class="card-title">
                <h2>@lang('backend.labels.recaptcha')</h2>
            </div>
            <div class="card-toolbar">
                <div class="rounded-circle w-15px h-15px {{ old('recaptcha_status', $security->recaptcha_status) == 1 ? 'bg-success' : 'bg-danger' }}"
                    id="kt_recaptcha_status_indicator"></div>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="mb-10 fv-row">
                <label class="form-label">@lang('backend.labels.status')</label>
                <select name="recaptcha_status" class="form-select mb-2 js-status-select @error('recaptcha_status') is-invalid @enderror"
                    data-control="select2" data-hide-search="true" data-indicator="#kt_recaptcha_status_indicator">
                    <option value="1"
                        {{ old('recaptcha_status', $security->recaptcha_status) == 1 ? 'selected' : '' }}>
                        @lang('backend.mixins.active')</option>
                    <option value="0"
                        {{ old('recaptcha_status', $security->recaptcha_status) == 0 ? 'selected' : '' }}>
                        @lang('backend.mixins.passive')</option>
                </select>
                @error('recaptcha_status')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <div class="text-muted fs-7">@lang('backend.notes.recaptcha')</div>
            </div>

            <div class="mb-10 fv-row">
                <label class="form-label">@lang('backend.labels.recaptcha_site_key')</label>
                <input type="password" name="recaptcha_site_key" autocomplete="new-password"
                    class="form-control mb-2 @error('recaptcha_site_key') is-invalid @enderror"
                    placeholder="{{ $security->recaptcha_site_key ? '••••••••••••••••' : trans('backend.placeholders.enter.recaptcha_site_key') }}" />
                @error('recaptcha_site_key')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="fv-row">
                <label class="form-label">@lang('backend.labels.recaptcha_secret_key')</label>
                <input type="password" name="recaptcha_secret_key" autocomplete="new-password"
                    class="form-control mb-2 @error('recaptcha_secret_key') is-invalid @enderror"
                    placeholder="{{ $security->recaptcha_secret_key ? '••••••••••••••••' : trans('backend.placeholders.enter.recaptcha_secret_key') }}" />
                @error('recaptcha_secret_key')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <div class="text-muted fs-7">@lang('backend.notes.recaptcha_keys')</div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary" id="kt_security_submit">
            <span class="indicator-label">@lang('backend.buttons.save')</span>
            <span class="indicator-progress">@lang('backend.labels.please_wait')
                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
            </span>
        </button>
    </div>
</form>
