@auth
    <div class="flex items-center gap-3">
        <button
            type="button"
            class="kt-btn kt-btn-ghost kt-btn-sm gap-1.5 topbar-quick-guide"
            title="{{ __('ui.quick_guide') }}"
            aria-label="{{ __('ui.quick_guide') }}"
            data-modal-url="{{ route('help.quick-guide') }}"
            data-modal-size="full"
            data-modal-no-history="1"
        >
            <i class="ki-filled ki-route text-base"></i>
            <span>{{ __('ui.quick_guide') }}</span>
        </button>
        <button
            type="button"
            class="kt-btn kt-btn-ghost kt-btn-sm gap-1.5"
            title="{{ __('ui.ba_guide') }}"
            aria-label="{{ __('ui.ba_guide') }}"
            data-help-url="{{ route('help.guide') }}"
        >
            <i class="ki-filled ki-book text-base"></i>
            <span>{{ __('ui.ba_guide') }}</span>
        </button>
        <div class="inline-flex" data-kt-dropdown="true" data-kt-dropdown-trigger="click">
            <button
                type="button"
                class="kt-btn kt-btn-ghost kt-btn-sm gap-1.5"
                data-kt-dropdown-toggle="true"
                aria-label="{{ auth()->user()->name }}"
                aria-haspopup="menu"
            >
                <span class="text-sm text-foreground">{{ auth()->user()->name }}</span>
                <i class="ki-filled ki-down text-xs text-muted-foreground"></i>
            </button>
            <div class="kt-dropdown-menu min-w-[160px]" data-kt-dropdown-menu="true">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="kt-dropdown-menu-link w-full text-start" data-kt-dropdown-dismiss="true">
                        Sign out
                    </button>
                </form>
            </div>
        </div>
    </div>
@else
    <span class="text-sm text-secondary-foreground font-medium hidden md:inline">
        {{ config('app.name') }}
    </span>
    <a href="{{ route('login') }}" class="kt-btn kt-btn-sm kt-btn-primary">
        Sign in
    </a>
@endauth
