{{--
    Collapsible section of a record view (Lineage, Details, Attachments, Comments, History, related lists).
    <x-section title="Comments" icon="messages" meta="2 open" key="comments" :open="false">
        <x-slot:badge> … </x-slot:badge>   <x-slot:actions> buttons, right-aligned </x-slot:actions>
        body
    </x-section>
    `key` lets the browser remember whether the reader left it open or closed (comments.js).
--}}
@props(['title', 'icon' => null, 'meta' => null, 'open' => true, 'key' => null])
<details {{ $attributes->class(['ba-section']) }} @if ($open) open @endif @if ($key) data-section-key="{{ $key }}" @endif>
    <summary class="ba-section__head">
        <i class="ki-filled ki-right ba-section__chev" aria-hidden="true"></i>
        @if ($icon)
            <i class="ki-filled ki-{{ $icon }} ba-section__icon" aria-hidden="true"></i>
        @endif
        <span class="ba-section__title">{{ $title }}</span>
        @if (filled($meta))
            <span class="ba-section__meta">{{ $meta }}</span>
        @endif
        @isset($badge)
            {{ $badge }}
        @endisset
        @isset($actions)
            <span class="ba-section__actions">{{ $actions }}</span>
        @endisset
    </summary>
    <div class="ba-section__body">
        {{ $slot }}
    </div>
</details>
