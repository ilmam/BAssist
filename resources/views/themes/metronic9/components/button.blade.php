@php
    /**
     * Allowed Metronic button vocabulary:
     *   color: primary | secondary | outline | ghost | destructive | mono
     *   size:  md (default, matches kt-input height) | sm (toolbars/chrome) | lg
     * Aliases: light→ghost, danger→destructive
     */
    $tag = $type === 'link' ? 'a' : 'button';
    $variant = match ($color) {
        'primary' => 'kt-btn-primary',
        'secondary' => 'kt-btn-secondary',
        'outline' => 'kt-btn-outline',
        'ghost', 'light' => 'kt-btn-ghost',
        'destructive', 'danger' => 'kt-btn-destructive',
        'mono' => 'kt-btn-mono',
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
