{{--
    Circular progress indicator. pct = null renders an empty "—" ring.
    <x-progress-ring :pct="72" size="64" />
--}}
@props(['pct' => null, 'size' => 56, 'stroke' => 6, 'label' => null])

@php
    $size = (int) $size;
    $stroke = (int) $stroke;
    $radius = ($size - $stroke) / 2;
    $circumference = 2 * M_PI * $radius;
    $value = $pct === null ? null : max(0, min(100, (int) $pct));
    $offset = $value === null ? $circumference : $circumference * (1 - $value / 100);
    $tone = $value === null ? 'neutral' : ($value >= 80 ? 'success' : ($value >= 50 ? 'warning' : 'danger'));
@endphp

<div {{ $attributes->class(['ba-ring', 'ba-ring--'.$tone]) }}
     style="width: {{ $size }}px; height: {{ $size }}px;"
     role="img"
     aria-label="{{ $label ?? ($value === null ? __('ui.readiness_not_started') : $value.'%') }}">
    <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 {{ $size }} {{ $size }}" aria-hidden="true">
        <circle class="ba-ring__track" cx="{{ $size / 2 }}" cy="{{ $size / 2 }}" r="{{ $radius }}" stroke-width="{{ $stroke }}" fill="none" />
        <circle class="ba-ring__value" cx="{{ $size / 2 }}" cy="{{ $size / 2 }}" r="{{ $radius }}" stroke-width="{{ $stroke }}" fill="none"
                stroke-linecap="round"
                stroke-dasharray="{{ round($circumference, 2) }}"
                stroke-dashoffset="{{ round($offset, 2) }}"
                transform="rotate(-90 {{ $size / 2 }} {{ $size / 2 }})" />
    </svg>
    <span class="ba-ring__text" style="font-size: {{ max(10, (int) round($size / ($value === 100 ? 5.2 : 4.4))) }}px;">{{ $value === null ? '—' : $value.'%' }}</span>
</div>
