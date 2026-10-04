<form class="form w-100" novalidate="novalidate" id="kt_sign_in_form" action="{{ route('backend.login') }}" method="POST">
    @csrf
    <div class="text-center mb-11">
        <h1 class="text-gray-900 fw-bolder mb-3">{{ config('app.name') }}</h1>
        <div class="text-gray-500 fw-semibold fs-6">@lang('backend.login.desc')</div>
    </div>
    <div class="fv-row mb-8">
        <input type="text" placeholder="@lang('backend.login.email')" name="email" autocomplete="off"
            class="form-control bg-transparent @error('email') is-invalid @enderror" value="{{ old('email') }}" />
        @error('email')
            <span class="fv-plugins-message-container invalid-feedback">{{ $message }}</span>
        @enderror
    </div>
    <div class="fv-row mb-3">
        <input type="password" placeholder="@lang('backend.login.password')" name="password" autocomplete="off"
            class="form-control bg-transparent @error('password') is-invalid @enderror" />
        @error('password')
            <span class="fv-plugins-message-container invalid-feedback">{{ $message }}</span>
        @enderror
    </div>
    <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
        <div>
            <div class="form-check form-check-custom form-check-solid">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1"
                    {{ old('remember') ? 'checked' : '' }} />
                <label class="form-check-label text-gray-700" for="remember">
                    @lang('backend.login.remember')
                </label>
            </div>
        </div>
    </div>
    @if ($security->isRecaptchaActive())
        <div class="fv-row mb-8">
            <div class="d-flex justify-content-center">
                <div class="g-recaptcha" data-sitekey="{{ $security->recaptcha_site_key }}"></div>
            </div>
            @error('g-recaptcha-response')
                <span class="fv-plugins-message-container invalid-feedback d-block text-center">{{ $message }}</span>
            @enderror
        </div>
    @endif
    <div class="d-grid mb-10">
        <button type="submit" id="kt_sign_in_submit" class="btn btn-primary" data-retry-after="{{ $retryAfter }}"
            {{ $retryAfter > 0 ? 'disabled' : '' }}>
            <span class="indicator-label">@lang('backend.buttons.login')</span>
            <span class="indicator-progress">@lang('backend.labels.please_wait')
                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
        </button>
    </div>
</form>
