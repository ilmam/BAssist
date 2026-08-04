<div data-help-title class="hidden">{{ $title }}</div>

<nav class="help-guide-nav help-guide-nav--top" aria-label="{{ __('ui.ba_guide') }}">
    @if ($prev)
        <a
            href="{{ route('help.guide.show', $prev['key']) }}"
            class="help-guide-nav__link"
            data-help-url="{{ route('help.guide.show', $prev['key']) }}"
            data-help-nav="prev"
        >
            ← {{ __('ui.ba_guide_previous') }}
        </a>
    @else
        <span class="help-guide-nav__spacer"></span>
    @endif

    <a
        href="{{ route('help.guide') }}"
        class="help-guide-nav__link help-guide-nav__contents"
        data-help-url="{{ route('help.guide') }}"
        data-help-nav="toc"
    >
        {{ __('ui.ba_guide_contents') }}
    </a>

    @if ($next)
        <a
            href="{{ route('help.guide.show', $next['key']) }}"
            class="help-guide-nav__link"
            data-help-url="{{ route('help.guide.show', $next['key']) }}"
            data-help-nav="next"
        >
            {{ __('ui.ba_guide_next') }} →
        </a>
    @else
        <span class="help-guide-nav__spacer"></span>
    @endif
</nav>

<div class="help-guide prose prose-sm max-w-none text-foreground">
    {!! $html !!}
</div>

<nav class="help-guide-nav help-guide-nav--bottom" aria-label="{{ __('ui.ba_guide') }}">
    @if ($prev)
        <a
            href="{{ route('help.guide.show', $prev['key']) }}"
            class="help-guide-nav__link"
            data-help-url="{{ route('help.guide.show', $prev['key']) }}"
            data-help-nav="prev"
        >
            ← {{ __('ui.ba_guide_previous') }}
        </a>
    @else
        <span class="help-guide-nav__spacer"></span>
    @endif

    <a
        href="{{ route('help.guide') }}"
        class="help-guide-nav__link help-guide-nav__contents"
        data-help-url="{{ route('help.guide') }}"
        data-help-nav="toc"
    >
        {{ __('ui.ba_guide_contents') }}
    </a>

    @if ($next)
        <a
            href="{{ route('help.guide.show', $next['key']) }}"
            class="help-guide-nav__link"
            data-help-url="{{ route('help.guide.show', $next['key']) }}"
            data-help-nav="next"
        >
            {{ __('ui.ba_guide_next') }} →
        </a>
    @else
        <span class="help-guide-nav__spacer"></span>
    @endif
</nav>
