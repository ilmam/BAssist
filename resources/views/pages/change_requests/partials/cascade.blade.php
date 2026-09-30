{{-- Change request impact preview: what this change touches, grouped by type. --}}
@php
    $cascade = $cascade ?? [];
    $typeLabels = [
        'feature' => __('ui.cr_impact_type_feature'),
        'scenario' => __('ui.cr_impact_type_scenario'),
        'functional_requirement' => __('ui.cr_impact_type_fr'),
        'non_functional_requirement' => __('ui.cr_impact_type_nfr'),
    ];
    $groups = [];
    foreach ($cascade as $item) {
        $groups[$item['type']][] = $item;
    }
    $summary = [];
    foreach ($groups as $type => $items) {
        $summary[] = trans_choice('ui.cr_impact_count_'.$type, count($items), ['count' => count($items)]);
    }
@endphp

<section class="ba-impact">
    <div class="ba-impact__head">
        <h3>{{ __('ui.change_request_cascade_title') }}</h3>
        @if ($cascade !== [])
            <x-status-badge :tone="count($cascade) >= 5 ? 'danger' : 'warning'">{{ trans_choice('ui.cr_impact_total', count($cascade), ['count' => count($cascade)]) }}</x-status-badge>
        @endif
    </div>
    <p class="ba-impact__help">{{ __('ui.change_request_cascade_help') }}</p>

    @if ($cascade === [])
        <x-empty-state icon="arrow-mix" :title="__('ui.cr_impact_empty_title')" :hint="__('ui.change_request_cascade_empty')" compact />
    @else
        <p class="ba-impact__summary">{{ __('ui.cr_impact_summary', ['list' => implode(', ', $summary)]) }}</p>
        <div class="ba-impact__groups">
            @foreach ($groups as $type => $items)
                <div class="ba-impact__group">
                    <div class="ba-section-label">{{ $typeLabels[$type] ?? \Illuminate\Support\Str::headline($type) }} · {{ count($items) }}</div>
                    <ul class="ba-item-list">
                        @foreach ($items as $item)
                            @php
                                $canOpen = ! empty($item['model']) && ! empty($item['id']) && entity_can($item['model'], 'view');
                                $modal = $canOpen ? model_modal_path($item['model'], 'view', $item['id']) : null;
                            @endphp
                            <li class="ba-item">
                                @if ($canOpen)
                                    <x-code-chip :code="$item['code'] ?? null" :href="model_route($item['model'], 'show', $item['id'])" :modal="$modal" data-modal-nav="off" />
                                    <a href="{{ model_route($item['model'], 'show', $item['id']) }}" class="ba-item__title js-open-modal" data-modal-url="{{ $modal }}" data-modal-nav="off">{{ $item['title'] }}</a>
                                @else
                                    <x-code-chip :code="$item['code'] ?? null" />
                                    <span class="ba-item__title">{{ $item['title'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    @endif
</section>
