@php
    /** @var list<string>|null $tags */
    $tags = $tags ?? [];
@endphp

@if ($tags !== [])
    <div class="gherkin-tags-preview" data-tags-display="{{ implode(' ', $tags) }}">
        @foreach ($tags as $tag)
            <span class="gherkin-tag-chip">{{ $tag }}</span>
        @endforeach
    </div>
@endif
