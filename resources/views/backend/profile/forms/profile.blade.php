<form action="{{ route('backend.profile') }}" method="POST" id="kt_account_profile_details_form" novalidate
    class="form d-flex flex-column flex-lg-row" autocomplete="off">
    @csrf
    @method('PATCH')

    <div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">
        <div class="card card-flush py-4">
            <div class="card-body text-center pt-9">
                <div class="image-input image-input-outline image-input-empty mb-3">
                    <div class="image-input-wrapper w-125px h-125px"
                        style="background-image: url('{{ admin()->first_image ? asset(admin()->first_image) : asset('backend/img/noimage.jpg') }}');">
                    </div>
                </div>
                <div class="text-gray-800 fs-4 fw-bold">{{ admin()->name }}</div>
                <div class="text-muted fs-7">{{ admin()->email }}</div>
            </div>
        </div>

        <div class="card card-flush py-4">
            <div class="card-header">
                <div class="card-title">
                    <h2>@lang('backend.labels.details')</h2>
                </div>
            </div>
            <div class="card-body pt-0 fs-6">
                <div class="mb-5">
                    <div class="fw-bold text-gray-800">@lang('backend.labels.role')</div>
                    <div class="text-gray-600">{{ optional(admin()->role)->name }}</div>
                </div>
                <div>
                    <div class="fw-bold text-gray-800">@lang('backend.labels.created_at')</div>
                    <div class="text-gray-600">{{ admin()->created_at->format('d.m.Y H:i') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
        <div class="card card-flush py-4">
            <div class="card-header">
                <div class="card-title">
                    <h2>@lang('backend.labels.general_information')</h2>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="mb-10 fv-row">
                    <label class="required form-label">@lang('backend.labels.name')</label>
                    <input type="text" name="name" required class="form-control mb-2 @error('name') is-invalid @enderror"
                        placeholder="@lang('backend.placeholders.enter.name')"
                        value="{{ old('name', admin()->name) }}" />
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-10 fv-row">
                    <label class="required form-label">@lang('backend.labels.email')</label>
                    <input type="email" name="email" required class="form-control mb-2 @error('email') is-invalid @enderror"
                        placeholder="@lang('backend.placeholders.enter.email')"
                        value="{{ old('email', admin()->email) }}" />
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="fv-row">
                    <label class="form-label">@lang('backend.labels.password')</label>
                    <div class="d-flex gap-2 mb-2">
                        <input type="password" name="password" id="password_input" autocomplete="new-password"
                            class="form-control @error('password') is-invalid @enderror"
                            placeholder="@lang('backend.profile.password')" />
                        <button type="button" class="btn btn-light-primary flex-shrink-0" id="generate_password_btn">
                            <i class="ki-outline ki-arrows-circle fs-4"></i> @lang('backend.buttons.generate_password')
                        </button>
                        <button type="button" class="btn btn-light flex-shrink-0" id="copy_password_btn">
                            <i class="ki-outline ki-copy fs-4"></i> @lang('backend.buttons.copy')
                        </button>
                    </div>
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    <div class="text-muted fs-7">@lang('backend.labels.password_help')</div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary" id="kt_account_profile_details_submit">
                <span class="indicator-label">@lang('backend.buttons.save')</span>
                <span class="indicator-progress">@lang('backend.labels.please_wait')
                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                </span>
            </button>
        </div>
    </div>
</form>
