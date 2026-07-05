@php
    $tag = $type === 'link' ? 'a' : 'button';
    $iconClass = $icon !== '' ? 'fa fa-'.$icon : '';
    $sizeClass = $size !== '' ? 'btn-'.$size : 'btn-md';
    $iconOnlyClass = $iconOnly !== '' ? 'btn-icon' : '';
    $widthClass = $width !== '' ? 'w-'.$width.'px' : '';
    $heightClass = $height !== '' ? 'h-'.$height.'px' : '';

    if ($class === '') {
        $class = trim('btn '.$sizeClass.' '.$iconOnlyClass.' btn-'.$color.' btn-active-light-'.$activeColor.' toggle '.$widthClass.' '.$heightClass);
    }

    $hrefUrl = ($type === 'link' && $href !== '')
        ? (str_contains($href, '://') || str_starts_with($href, '/') ? $href : URL::route($href))
        : '';
@endphp

<{{ $tag }}
    class="{{ $class }}"
    {{ $attributes }}
    @if ($onclick !== '')
        onclick="{{ $onclick }}"
    @endif
    @if ($hrefUrl !== '')
        href="{{ $hrefUrl }}"
    @elseif ($type !== 'link')
        type="{{ $type }}"
    @endif
    >    @if ($iconClass !== '')
        <i class="{{ $iconClass }}"></i>
    @endif
    {{ $slot }}
</{{ $tag }}>
