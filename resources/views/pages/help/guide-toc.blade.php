<div data-help-title class="hidden">{{ $title }}</div>

<div class="help-guide-toc">
    <p class="help-guide-toc__intro text-sm text-muted-foreground">
        {{ __('ui.ba_guide_intro') }}
    </p>

    <ol class="help-guide-toc__list">
        @foreach ($steps as $step)
            <li>
                <a
                    href="{{ route('help.guide.show', $step['key']) }}"
                    class="help-guide-toc__link"
                    data-help-url="{{ route('help.guide.show', $step['key']) }}"
                >
                    <span class="help-guide-toc__index">{{ $loop->iteration }}</span>
                    <span class="help-guide-toc__label">{{ $step['label'] }}</span>
                </a>
            </li>
        @endforeach
    </ol>
</div>
