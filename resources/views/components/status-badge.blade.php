{{--
    Consistent status / priority / risk-level badge.
    <x-status-badge :value="$item->status?->name" />            tone derived via ui_status_tone()
    <x-status-badge value="Critical" tone="danger" />            explicit tone
--}}
@props(['value' => null, 'tone' => null, 'size' => 'sm'])

@php
    $text = is_string($value) ? trim($value) : (string) ($value ?? '');
    $resolved = $tone ?: ui_status_tone($text);
@endphp

@if ($text !== '' || trim((string) $slot) !== '')
    <span {{ $attributes->class(['ba-badge', 'ba-badge--'.$resolved, 'ba-badge--lg' => $size === 'md']) }}>
        <span class="ba-badge__dot" aria-hidden="true"></span>
        {{ trim((string) $slot) !== '' ? $slot : $text }}
    </span>
@endif
