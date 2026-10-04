<div id="kt_app_header" class="app-header">
    <div class="app-header-logo d-flex align-items-center ps-lg-10 gap-4 gap-lg-6">
        <button id="kt_app_sidebar_mobile_toggle"
            class="btn btn-icon bg-gray-300 bg-opacity-75 btn-color-gray-700 btn-active-color-primary rounded-1 w-30px h-30px ms-n3 ms-lg-0 d-lg-none">
            <i class="ki-outline ki-abstract-14 fs-3"></i>
        </button>
        <button id="kt_app_aside_toggle"
            class="btn btn-icon bg-gray-300 bg-opacity-75 btn-color-gray-700 btn-active-color-primary rounded-1 w-30px h-30px ms-n3 ms-lg-0 d-none d-lg-flex">
            <i class="ki-outline ki-burger-menu-6 fs-3"></i>
        </button>
        <a href="{{ route('backend.dashboard') }}">
            <img alt="Logo" src="{{ asset('backend/img/black-logo.png') }}"
                class="h-50px d-sm-none d-inline theme-light-show" />
            <img alt="Logo" src="{{ asset('backend/img/black-logo.png') }}"
                class="h-50px h-lg-50px theme-light-show d-none d-sm-inline" />
            <img alt="Logo" src="{{ asset('backend/img/white-logo.png') }}"
                class="h-50px h-lg-50px theme-dark-show" />
        </a>
    </div>
    <div class="app-header-wrapper">
        <div class="app-container container-fluid">
            <div class="app-navbar-item d-flex align-items-stretch flex-lg-grow-1 me-1 me-lg-0">
                <div id="kt_header_search" class="header-search d-flex align-items-center w-lg-400px">
                    <form class="d-none d-lg-block w-100 position-relative mb-5 mb-lg-0" autocomplete="off">
                        <input type="hidden" />
                        <i
                            class="ki-outline ki-magnifier search-icon fs-2 text-gray-500 position-absolute top-50 translate-middle-y ms-5"></i>
                        <input type="text" class="search-input form-control form-control rounded-1 ps-13"
                            name="search" value="" placeholder="@lang('backend.datatables.search')"
                            data-kt-search-element="input" />
                        <span
                            class="search-spinner position-absolute top-50 end-0 translate-middle-y lh-0 d-none me-5">
                            <span class="spinner-border h-15px w-15px align-middle text-gray-500"></span>
                        </span>
                        <span
                            class="search-reset btn btn-flush btn-active-color-primary position-absolute top-50 end-0 translate-middle-y lh-0 d-none me-4"
                            data-kt-search-element="clear">
                            <i class="ki-outline ki-cross fs-2 fs-lg-1 me-0"></i>
                        </span>
                    </form>
                </div>
            </div>
            <div class="app-navbar flex-shrink-0">
                <div class="app-navbar-item ms-1 ms-lg-3 me-2 me-lg-6" id="kt_header_user_menu_toggle">
                    <div class="cursor-pointer symbol symbol-35px symbol-md-40px"
                        data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-attach="parent"
                        data-kt-menu-placement="bottom-end">
                        <img class="symbol symbol-circle symbol-35px symbol-md-40px"
                            src="{{ asset('backend/src/media/avatars/300-1.jpg') }}" alt="user">
                    </div>
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px"
                        data-kt-menu="true">
                        <div class="menu-item px-3">
                            <div class="menu-content d-flex align-items-center px-3">
                                <div class="symbol symbol-50px me-5 symbol-light-danger">
                                    <img class="symbol symbol-circle symbol-35px symbol-md-40px"
                                        src="{{ asset('backend/src/media/avatars/300-1.jpg') }}" alt="user">
                                </div>
                                <div class="d-flex flex-column">
                                    <div class="fw-bold d-flex align-items-center fs-5">{{ admin()->name }}</div>
                                    <a href="#"
                                        class="fw-semibold text-muted text-hover-primary fs-7">{{ admin()->email }}</a>
                                </div>
                            </div>
                        </div>
                        <div class="separator my-2"></div>
                        <div class="menu-item px-5">
                            <a href="{{ route('backend.profile') }}" class="menu-link px-5">@lang('backend.titles.profile')</a>
                        </div>

                        <div class="separator my-2"></div>
                        <div class="menu-item px-5" data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
                            data-kt-menu-placement="left-start" data-kt-menu-offset="-15px, 0">
                            <a href="#" class="menu-link px-5">
                                <span class="menu-title position-relative">@lang('backend.labels.mode')
                                    <span class="ms-5 position-absolute translate-middle-y top-50 end-0">
                                        <i class="ki-outline ki-night-day theme-light-show fs-2"></i>
                                        <i class="ki-outline ki-moon theme-dark-show fs-2"></i>
                                    </span></span>
                            </a>
                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-title-gray-700 menu-icon-gray-500 menu-active-bg menu-state-color fw-semibold py-4 fs-base w-150px"
                                data-kt-menu="true" data-kt-element="theme-mode-menu">
                                <div class="menu-item px-3 my-0">
                                    <a href="#" class="menu-link px-3 py-2" data-kt-element="mode"
                                        data-kt-value="light">
                                        <span class="menu-icon" data-kt-element="icon">
                                            <i class="ki-outline ki-night-day fs-2"></i>
                                        </span>
                                        <span class="menu-title">@lang('backend.labels.light')</span>
                                    </a>
                                </div>
                                <div class="menu-item px-3 my-0">
                                    <a href="#" class="menu-link px-3 py-2" data-kt-element="mode"
                                        data-kt-value="dark">
                                        <span class="menu-icon" data-kt-element="icon">
                                            <i class="ki-outline ki-moon fs-2"></i>
                                        </span>
                                        <span class="menu-title">@lang('backend.labels.dark')</span>
                                    </a>
                                </div>
                                <div class="menu-item px-3 my-0">
                                    <a href="#" class="menu-link px-3 py-2" data-kt-element="mode"
                                        data-kt-value="system">
                                        <span class="menu-icon" data-kt-element="icon">
                                            <i class="ki-outline ki-screen fs-2"></i>
                                        </span>
                                        <span class="menu-title">@lang('backend.labels.system')</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="menu-item px-5" data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
                            data-kt-menu-placement="left-start" data-kt-menu-offset="-15px, 0">

                            <a href="#" class="menu-link px-5">
                                <span class="menu-title position-relative">
                                    @lang('backend.titles.languages')

                                    <span
                                        class="fs-8 rounded bg-light px-3 py-2 position-absolute translate-middle-y top-50 end-0">
                                        {{ current_lang() }}
                                        <img class="w-15px h-15px rounded-1 ms-2"
                                            src="{{ asset('backend/src/media/flags/' . app()->getLocale() . '.svg') }}"
                                            alt="{{ app()->getLocale() }}" />
                                    </span>
                                </span>
                            </a>

                            <div class="menu-sub menu-sub-dropdown w-175px py-4">
                                @foreach (active_langs() as $lang)
                                    <div class="menu-item px-3">
                                        <a href="javascript:void(0);"
                                            class="menu-link d-flex px-5{{ app()->getLocale() === $lang->code ? ' active' : '' }}"
                                            onclick="event.preventDefault(); document.getElementById('lang-{{ $lang->code }}').submit();">

                                            <span class="symbol symbol-20px me-4">
                                                <img class="rounded-1"
                                                    src="{{ asset('backend/src/media/flags/' . $lang->code . '.svg') }}"
                                                    alt="{{ $lang->code }}" />
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
                        <div class="menu-item px-5">
                            <a href="{{ route('backend.logout') }}" class="menu-link px-5"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                @lang('backend.buttons.logout')
                            </a>
                            <form id="logout-form" action="{{ route('backend.logout') }}" method="POST"
                                class="d-none">
                                @csrf
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
