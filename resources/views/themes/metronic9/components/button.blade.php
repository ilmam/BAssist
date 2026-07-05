@php
    $tag = $type === 'link' ? 'a' : 'button';
    $variant = match ($color) {
        'primary' => 'kt-btn-primary',
        'danger' => 'kt-btn-destructive',
        'secondary' => 'kt-btn-secondary',
        'light' => 'kt-btn-ghost',
        default => 'kt-btn-outline',
    };
    $sizeClass = match ($size) {
        'sm' => 'kt-btn-sm',
        'lg' => 'kt-btn-lg',
        default => '',
    };
    $iconOnlyClass = $iconOnly !== '' ? 'kt-btn-icon' : '';
    $classes = trim('kt-btn '.$variant.' '.$sizeClass.' '.$iconOnlyClass.' '.$class);
    $iconName = match ($icon) {
        'arrow-left' => 'black-left-line',
        'plus' => 'plus',
        'pencil' => 'notepad-edit',
        'trash' => 'trash',
        'eye' => 'eye',
        default => $icon,
    };
    $hrefUrl = ($type === 'link' && $href !== '')
        ? (str_contains($href, '://') || str_starts_with($href, '/') ? $href : URL::route($href))
        : '';
@endphp

<{{ $tag }}
    class="{{ $classes }}"
    {{ $attributes }}
    @if ($onclick !== '')
        onclick="{{ $onclick }}"
    @endif
    @if ($hrefUrl !== '')
        href="{{ $hrefUrl }}"
    @elseif ($type !== 'link')
        type="{{ $type }}"
    @endif
    >    @if ($icon !== '')
        <i class="ki-filled ki-{{ $iconName }}"></i>
    @endif
    {{ $slot }}
</{{ $tag }}>
