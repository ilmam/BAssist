{{-- One entity row on the home page (awaiting decision / recent activity). --}}
<li class="ba-item">
    <span class="ba-item__icon" title="{{ $item['entity_label'] }}"><i class="ki-filled ki-{{ $item['icon'] }}"></i></span>
    <span class="min-w-0 flex-1">
        <span class="flex items-center gap-2 min-w-0">
            <x-code-chip :code="$item['code']" :href="$item['url']" :modal="$item['modal']" data-modal-nav="off" />
            <a href="{{ $item['url'] }}" class="ba-item__title js-open-modal" data-modal-url="{{ $item['modal'] }}" data-modal-nav="off">{{ $item['title'] ?: '—' }}</a>
        </span>
        <span class="ba-item__meta">
            {{ $item['entity_label'] }}
            @if ($item['project']) · {{ $item['project'] }} @endif
            @if ($item['updated_at']) · {{ $item['updated_at']->diffForHumans() }} @endif
        </span>
    </span>
    <x-status-badge :value="$item['status']" class="shrink-0" />
</li>
