@extends('layouts.backend.master')
@section('title', trans('backend.titles.profile'))
@section('content')
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_toolbar" class="app-toolbar pt-7 pt-lg-10">
            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                    <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                        @lang('backend.labels.edit_profile')
                    </h1>
                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ route('backend.dashboard') }}" class="text-muted text-hover-primary">@lang('backend.titles.dashboard')</a>
                        </li>
                        <li class="breadcrumb-item">
                            <span class="bullet bg-gray-400 w-5px h-2px"></span>
                        </li>
                        <li class="breadcrumb-item text-muted">@lang('backend.titles.profile')</li>
                    </ul>
                </div>
            </div>
        </div>

        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                @if ($security)
                    <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-8">
                        <li class="nav-item">
                            <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab"
                                href="#kt_profile_general">@lang('backend.titles.profile')</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab"
                                href="#kt_profile_security">@lang('backend.labels.security')</a>
                        </li>
                    </ul>
                @endif

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="kt_profile_general" role="tabpanel">
                        @include('backend.profile.forms.profile')
                    </div>

                    @if ($security)
                        <div class="tab-pane fade" id="kt_profile_security" role="tabpanel">
                            @include('backend.profile.forms.security')
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function() {
            const securityTab = document.querySelector('[data-bs-toggle="tab"][href="' + window.location.hash + '"]');

            if (securityTab) {
                new bootstrap.Tab(securityTab).show();
            }
        });

        $('.js-status-select').on('change', function() {
            $($(this).data('indicator'))
                .toggleClass('bg-success', this.value == 1)
                .toggleClass('bg-danger', this.value != 1);
        });

        $('#kt_security_form').on('submit', function() {
            const submitButton = $('#kt_security_submit');
            submitButton.attr('data-kt-indicator', 'on');
            submitButton.prop('disabled', true);
        });

        $('#kt_account_profile_details_form').on('submit', function(e) {
            if (!ktValidateRequired(this)) {
                e.preventDefault();
                return;
            }

            const submitButton = $('#kt_account_profile_details_submit');
            submitButton.attr('data-kt-indicator', 'on');
            submitButton.prop('disabled', true);
        });

        function generateStrongPassword(length = 12) {
            const lower = 'abcdefghijklmnopqrstuvwxyz';
            const upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            const digits = '0123456789';
            const symbols = '!@#$%^&*()-+=';
            const all = lower + upper + digits + symbols;

            let password = [
                upper[Math.floor(Math.random() * upper.length)],
                lower[Math.floor(Math.random() * lower.length)],
                digits[Math.floor(Math.random() * digits.length)],
                symbols[Math.floor(Math.random() * symbols.length)],
            ];

            for (let i = password.length; i < length; i++) {
                password.push(all[Math.floor(Math.random() * all.length)]);
            }

            for (let i = password.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [password[i], password[j]] = [password[j], password[i]];
            }

            return password.join('');
        }

        $('#generate_password_btn').on('click', function() {
            const input = document.getElementById('password_input');
            input.type = 'text';
            input.value = generateStrongPassword();
        });

        $('#copy_password_btn').on('click', function() {
            const input = document.getElementById('password_input');
            if (!input.value) {
                return;
            }

            navigator.clipboard.writeText(input.value).then(function() {
                const btn = $('#copy_password_btn');
                const originalHtml = btn.html();
                btn.html('<i class="ki-outline ki-check fs-4"></i> @lang('backend.buttons.copied')');
                setTimeout(function() {
                    btn.html(originalHtml);
                }, 1500);
            });
        });
    </script>
@endsection
