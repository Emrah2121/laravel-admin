@extends('layouts.backend.master')

@section('title', trans('backend.titles.login'))

@section('styles')
    <style>
        body {
            background-image: url("{{ asset('backend/src/media/auth/bg10.jpeg') }}");
        }

        [data-bs-theme="dark"] body {
            background-image: url("{{ asset('backend/src/media/auth/bg10-dark.jpeg') }}");
        }
    </style>
@endsection

@section('content')
    <div class="d-flex flex-column flex-lg-row flex-column-fluid">
        <div class="d-flex flex-lg-row-fluid">
            <div class="d-flex flex-column flex-center pb-0 pb-lg-10 p-10 w-100">
                <img class="theme-light-show mx-auto mw-100 w-150px w-lg-300px mb-10 mb-lg-20"
                    src="{{ asset('backend/src/media/auth/agency.png') }}" alt="" />
                <img class="theme-dark-show mx-auto mw-100 w-150px w-lg-300px mb-10 mb-lg-20"
                    src="{{ asset('backend/src/media/auth/agency-dark.png') }}" alt="" />
            </div>
        </div>
        <div class="d-flex flex-column-fluid flex-lg-row-auto justify-content-center justify-content-lg-end p-12">
            <div class="bg-body d-flex flex-column flex-center rounded-4 w-md-600px p-10">
                <div class="d-flex flex-center flex-column align-items-stretch h-lg-100 w-md-400px">
                    <div class="d-flex flex-center flex-column flex-column-fluid pb-15 pb-lg-20">
                        @include('backend.login.forms.login')
                    </div>
                    <div class="d-flex flex-stack">
                        <div class="me-10">
                            <button class="btn btn-flex btn-link btn-color-gray-700 btn-active-color-primary rotate fs-base"
                                data-kt-menu-trigger="click" data-kt-menu-placement="bottom-start"
                                data-kt-menu-offset="0px, 0px">

                                <img class="w-20px h-20px rounded me-3"
                                    src="{{ asset('backend/src/media/flags/' . app()->getLocale() . '.svg') }}"
                                    alt="">

                                <span class="me-1">{{ current_lang() }}</span>
                                <i class="ki-outline ki-down fs-5 text-muted rotate-180 m-0"></i>
                            </button>

                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800
                menu-state-bg-light-primary fw-semibold w-200px py-4 fs-7"
                                data-kt-menu="true">

                                @foreach (active_langs() as $lang)
                                    <div class="menu-item px-3">
                                        <a href="javascript:void(0);"
                                            class="menu-link d-flex px-5{{ app()->getLocale() === $lang->code ? ' active' : '' }}"
                                            onclick="event.preventDefault(); document.getElementById('lang-{{ $lang->code }}').submit();">

                                            <span class="symbol symbol-20px me-4">
                                                <img class="rounded-1"
                                                    src="{{ asset('backend/src/media/flags/' . $lang->code . '.svg') }}"
                                                    alt="{{ $lang->name }}">
                                            </span>

                                            {{ $lang->name }}
                                        </a>

                                        <form action="{{ route('backend.lang') }}" method="POST"
                                            id="lang-{{ $lang->code }}" class="d-none">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="lang" value="{{ $lang->code }}">
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="d-flex fw-semibold text-primary fs-7 gap-5">
                            <a>© {{ date('Y') }}
                                @lang('backend.login.copyright')</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @if ($security->isRecaptchaActive())
        <script>
            document.querySelector('.g-recaptcha').dataset.theme =
                document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';
        </script>
        <script src="https://www.google.com/recaptcha/api.js?hl={{ app()->getLocale() }}" async defer></script>
    @endif
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('#kt_sign_in_form');
            const submitButton = document.querySelector('#kt_sign_in_submit');

            if (form) {
                form.addEventListener('submit', function() {
                    if (submitButton) {
                        submitButton.setAttribute('data-kt-indicator', 'on');
                        submitButton.disabled = true;
                    }
                });
            }

            let secondsLeft = parseInt(submitButton?.dataset.retryAfter, 10) || 0;

            if (secondsLeft > 0) {
                const label = submitButton.querySelector('.indicator-label');
                const originalLabel = label.textContent;

                const formatTime = (seconds) =>
                    Math.floor(seconds / 60) + ':' + String(seconds % 60).padStart(2, '0');

                const tick = () => {
                    if (secondsLeft <= 0) {
                        clearInterval(timer);
                        label.textContent = originalLabel;
                        submitButton.disabled = false;
                        return;
                    }

                    label.textContent = originalLabel + ' (' + formatTime(secondsLeft) + ')';
                    secondsLeft--;
                };

                const timer = setInterval(tick, 1000);
                tick();
            }
        });
    </script>
@endsection
