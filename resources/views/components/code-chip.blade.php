{{--
    Entity code chip (BN-4, FR-12 …). Pass modal to open in the shared overlay.
    <x-code-chip code="FR-12" :href="$pageUrl" :modal="$modalUrl" />
--}}
@props(['code' => null, 'href' => null, 'modal' => null])

@if (filled($code))
    @if ($href)
        <a href="{{ $href }}"
           {{ $attributes->class(['ba-code-chip', 'js-open-modal' => (bool) $modal]) }}
           @if ($modal) data-modal-url="{{ $modal }}" @endif>{{ $code }}</a>
    @else
        <span {{ $attributes->class(['ba-code-chip']) }}>{{ $code }}</span>
    @endif
@endif
