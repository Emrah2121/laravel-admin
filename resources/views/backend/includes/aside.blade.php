<div id="kt_app_aside" class="app-aside flex-column" data-kt-drawer="true"
    data-kt-drawer-name="app-aside" data-kt-drawer-activate="true" data-kt-drawer-overlay="true"
    data-kt-drawer-width="auto" data-kt-drawer-direction="start"
    data-kt-drawer-toggle="#kt_app_aside_toggle">
    <div id="kt_app_aside_wrapper" class="app-aside-wrapper hover-scroll-y my-4" data-kt-scroll="true"
        data-kt-scroll-activate="true" data-kt-scroll-height="auto" data-kt-scroll-offset="5px">
        <div id="kt_app_aside_menu"
            class="menu menu-sub-indention menu-rounded menu-column fw-semibold fs-6 px-2"
            data-kt-menu="true">

            <div class="menu-item pt-5">
                <div class="menu-content">
                    <span class="menu-heading fw-bold text-uppercase fs-7">@lang('backend.labels.quick_links')</span>
                </div>
            </div>

            <div class="menu-item">
                <a class="menu-link" href="{{ route('backend.dashboard') }}">
                    <span class="menu-icon">
                        <i class="ki-outline ki-element-11 fs-2"></i>
                    </span>
                    <span class="menu-title">@lang('backend.titles.dashboard')</span>
                </a>
            </div>

        </div>
    </div>
</div>
