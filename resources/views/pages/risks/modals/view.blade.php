@php
    $modelName = class_basename($model);
    $critical = (bool) ($dto->is_critical ?? false);
    $gap = (bool) ($dto->has_coverage_gap ?? false);
@endphp

<x-modal-content :title="$modelName.' Details'">
    @if ($critical || $gap)
        <div class="risk-alert risk-alert--{{ $gap ? 'gap' : 'critical' }} mb-5">
            @if ($critical)
                <strong>{{ __('ui.risk_critical_highlight') }}:</strong>
                {{ $dto->score_label }}
            @endif
            @if ($gap)
                <div class="mt-1">{{ __('ui.risk_coverage_gap') }}</div>
            @endif
        </div>
    @endif

    <x-details-view
        model="{{ $modelName }}"
        :dto="$dto"
        :fields="$fields"
    />
</x-modal-content>
