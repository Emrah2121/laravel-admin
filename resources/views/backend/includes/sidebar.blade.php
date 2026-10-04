<div id="kt_app_sidebar" class="app-sidebar flex-column" data-kt-drawer="true" data-kt-drawer-name="app-sidebar"
    data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="auto"
    data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">
    <div id="kt_app_sidebar_wrapper" class="app-sidebar-wrapper hover-scroll-y mx-3 my-2" data-kt-scroll="true"
        data-kt-scroll-activate="true" data-kt-scroll-height="auto" data-kt-scroll-dependencies="#kt_app_sidebar_header"
        data-kt-scroll-offset="5px">
        <div id="kt_app_sidebar_menu"
            class="menu menu-sub-indention menu-rounded menu-column fw-semibold fs-6 py-4 py-lg-6 px-2"
            data-kt-menu="true">

            <div class="menu-item pt-1">
                <div class="menu-content">
                    <span class="menu-section fs-8 fw-bold text-uppercase text-gray-500 ps-1">@lang('backend.labels.menu')</span>
                </div>
            </div>

            <div class="menu-item">
                <a href="{{ route('backend.dashboard') }}"
                    class="menu-link {{ request()->routeIs('backend.dashboard') ? 'active' : '' }}">
                    <span class="menu-icon">
                        <i class="ki-outline ki-home fs-2"></i>
                    </span>
                    <span class="menu-title">@lang('backend.titles.dashboard')</span>
                </a>
            </div>

            @can('blogs index')
                <div class="menu-item pt-5">
                    <div class="menu-content">
                        <span class="menu-section fs-8 fw-bold text-uppercase text-gray-500 ps-1">@lang('backend.titles.pages')</span>
                    </div>
                </div>

                <div class="menu-item">
                    <a href="{{ route('backend.blogs.index') }}"
                        class="menu-link {{ request()->routeIs('backend.blogs.*') ? 'active' : '' }}">
                        <span class="menu-icon">
                            <i class="ki-outline ki-book-square fs-2"></i>
                        </span>
                        <span class="menu-title">@lang('backend.titles.blogs')</span>
                    </a>
                </div>
            @endcan

            @if (admin()->can('admins index') || admin()->can('roles index') || admin()->can('permissions index'))
                <div class="menu-item pt-5">
                    <div class="menu-content">
                        <span class="menu-section fs-8 fw-bold text-uppercase text-gray-500 ps-1">@lang('backend.titles.admins')</span>
                    </div>
                </div>

                @can('admins index')
                    <div class="menu-item">
                        <a href="{{ route('backend.admins.index') }}"
                            class="menu-link {{ request()->routeIs('backend.admins.*') ? 'active' : '' }}">
                            <span class="menu-icon">
                                <i class="ki-outline ki-profile-circle fs-2"></i>
                            </span>
                            <span class="menu-title">@lang('backend.titles.admins')</span>
                        </a>
                    </div>
                @endcan

                @can('roles index')
                    <div class="menu-item">
                        <a href="{{ route('backend.roles.index') }}"
                            class="menu-link {{ request()->routeIs('backend.roles.*') ? 'active' : '' }}">
                            <span class="menu-icon">
                                <i class="ki-outline ki-shield-tick fs-2"></i>
                            </span>
                            <span class="menu-title">@lang('backend.titles.roles')</span>
                        </a>
                    </div>
                @endcan

                @can('permissions index')
                    <div class="menu-item">
                        <a href="{{ route('backend.permissions.index') }}"
                            class="menu-link {{ request()->routeIs('backend.permissions.*') ? 'active' : '' }}">
                            <span class="menu-icon">
                                <i class="ki-outline ki-lock-2 fs-2"></i>
                            </span>
                            <span class="menu-title">@lang('backend.titles.permissions')</span>
                        </a>
                    </div>
                @endcan
            @endif

            @if (admin()->can('languages index') || admin()->can('translations index'))
                <div class="menu-item pt-5">
                    <div class="menu-content">
                        <span class="menu-section fs-8 fw-bold text-uppercase text-gray-500 ps-1">@lang('backend.titles.languages')</span>
                    </div>
                </div>

                @can('languages index')
                    <div class="menu-item">
                        <a href="{{ route('backend.languages.index') }}"
                            class="menu-link {{ request()->routeIs('backend.languages.*') ? 'active' : '' }}">
                            <span class="menu-icon">
                                <i class="ki-outline ki-geolocation fs-2"></i>
                            </span>
                            <span class="menu-title">@lang('backend.titles.languages')</span>
                        </a>
                    </div>
                @endcan

                @can('translations index')
                    <div class="menu-item">
                        <a href="{{ route('backend.translations.index') }}"
                            class="menu-link {{ request()->routeIs('backend.translations.*') ? 'active' : '' }}">
                            <span class="menu-icon">
                                <i class="bi-outline bi-translate fs-2"></i>
                            </span>
                            <span class="menu-title">@lang('backend.titles.translations')</span>
                        </a>
                    </div>
                @endcan
            @endif

            @can('logs index')
                <div class="menu-item pt-5">
                    <div class="menu-content">
                        <span class="menu-section fs-8 fw-bold text-uppercase text-gray-500 ps-1">@lang('backend.titles.logs')</span>
                    </div>
                </div>

                <div class="menu-item">
                    <a href="{{ route('backend.logs.index') }}"
                        class="menu-link {{ request()->routeIs('backend.logs.*') ? 'active' : '' }}">
                        <span class="menu-icon">
                            <i class="ki-outline ki-document fs-2"></i>
                        </span>
                        <span class="menu-title">@lang('backend.titles.logs')</span>
                    </a>
                </div>
            @endcan

            @can('settings index')
                <div class="menu-item pt-5">
                    <div class="menu-content">
                        <span class="menu-section fs-8 fw-bold text-uppercase text-gray-500 ps-1">@lang('backend.titles.settings')</span>
                    </div>
                </div>

                <div class="menu-item">
                    <a href="{{ route('backend.settings.index') }}"
                        class="menu-link {{ request()->routeIs('backend.settings.*') ? 'active' : '' }}">
                        <span class="menu-icon">
                            <i class="ki-outline ki-setting-2 fs-2"></i>
                        </span>
                        <span class="menu-title">@lang('backend.titles.settings')</span>
                    </a>
                </div>
            @endcan
        </div>
    </div>
</div>
