@auth
    <div class="flex items-center gap-3">
        <button type="button" class="ba-palette-trigger" data-palette-open aria-label="{{ __('ui.palette_label') }}" aria-keyshortcuts="Control+K Meta+K">
            <i class="ki-filled ki-magnifier" aria-hidden="true"></i>
            <span>{{ __('ui.palette_trigger') }}</span>
            <kbd>Ctrl K</kbd>
        </button>
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
                <div class="px-3 pt-2 pb-1.5" role="group" aria-label="{{ __('ui.theme_appearance') }}">
                    <div class="text-xs font-medium text-muted-foreground mb-1.5">{{ __('ui.theme_appearance') }}</div>
                    <div class="inline-flex w-full rounded-md border border-border overflow-hidden">
                        @foreach (['light' => 'sun', 'dark' => 'moon', 'system' => 'screen'] as $mode => $icon)
                            <button type="button" data-kt-theme-switch-set="{{ $mode }}"
                                class="theme-choice flex-1 inline-flex items-center justify-center gap-1 px-2 py-1.5 text-xs text-foreground hover:bg-accent">
                                <i class="ki-filled ki-{{ $icon }}" aria-hidden="true"></i>{{ __('ui.theme_'.$mode) }}
                            </button>
                        @endforeach
                    </div>
                </div>
                <div class="border-t border-border my-1"></div>
                <a href="{{ route('profile.api-tokens.index') }}" class="kt-dropdown-menu-link w-full text-start">
                    {{ __('ui.api_tokens') }}
                </a>
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
