{{--
    Global help drawer host. Content is loaded via fetch into [data-help-drawer-body].
    Uses Metronic9 KTDrawer (end) when available; falls back to open-class toggling.
--}}
<div
    id="{{ $id }}"
    class="hidden"
    data-kt-drawer="true"
    data-kt-drawer-class="kt-drawer kt-drawer-end flex top-0 bottom-0 w-full max-w-[440px] z-[60]"
    data-kt-drawer-overlay="true"
    data-help-drawer
>
    <div class="kt-drawer-header">
        <h3 class="kt-drawer-title" data-help-drawer-title>{{ __('ui.help') }}</h3>
        <button type="button" class="kt-drawer-close" data-kt-drawer-dismiss="true" aria-label="{{ __('ui.close') }}">
            <i class="ki-filled ki-cross"></i>
        </button>
    </div>
    <div class="kt-drawer-content" data-help-drawer-body>
        <p class="text-sm text-muted-foreground">{{ __('ui.help_loading') }}</p>
    </div>
</div>
