<div class="kt-sidebar bg-background border-e border-e-border fixed top-0 bottom-0 z-20 hidden lg:flex flex-col items-stretch shrink-0 [--kt-drawer-enable:true] lg:[--kt-drawer-enable:false]"
    data-kt-drawer="true"
    data-kt-drawer-class="kt-drawer kt-drawer-start top-0 bottom-0"
    id="sidebar">
    <div class="kt-sidebar-header hidden lg:flex items-center relative justify-between px-3 lg:px-6 shrink-0" id="sidebar_header">
        <a class="dark:hidden" href="{{ url('/') }}">
            <img class="default-logo min-h-[22px] max-w-none" src="{{ ui_asset('media/app/default-logo.svg') }}" alt="{{ config('app.name') }}" />
            <img class="small-logo min-h-[22px] max-w-none" src="{{ ui_asset('media/app/mini-logo.svg') }}" alt="{{ config('app.name') }}" />
        </a>
        <a class="hidden dark:block" href="{{ url('/') }}">
            <img class="default-logo min-h-[22px] max-w-none" src="{{ ui_asset('media/app/default-logo-dark.svg') }}" alt="{{ config('app.name') }}" />
            <img class="small-logo min-h-[22px] max-w-none" src="{{ ui_asset('media/app/mini-logo.svg') }}" alt="{{ config('app.name') }}" />
        </a>
        <button class="kt-btn kt-btn-outline kt-btn-icon size-[30px] absolute start-full top-2/4 -translate-x-2/4 -translate-y-2/4 rtl:translate-x-2/4"
            data-kt-toggle="body"
            data-kt-toggle-class="kt-sidebar-collapse"
            id="sidebar_toggle"
            type="button">
            <i class="ki-filled ki-black-left-line kt-toggle-active:rotate-180 transition-all duration-300 rtl:translate rtl:rotate-180 rtl:kt-toggle-active:rotate-0"></i>
        </button>
    </div>
    <div class="kt-sidebar-content flex grow shrink-0 py-5 pe-2" id="sidebar_content">
        <div class="kt-scrollable-y-hover grow shrink-0 flex ps-2 lg:ps-5 pe-1 lg:pe-3"
            data-kt-scrollable="true"
            data-kt-scrollable-dependencies="#sidebar_header"
            data-kt-scrollable-height="auto"
            data-kt-scrollable-offset="0px"
            data-kt-scrollable-wrappers="#sidebar_content"
            id="sidebar_scrollable">
            @include('themes.metronic9.partials.menu')
        </div>
    </div>
</div>
