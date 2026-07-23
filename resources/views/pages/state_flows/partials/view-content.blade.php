@php
    use App\Services\StateDiagramMermaidGenerator;

    $modelName = class_basename($model);
    $transitions = is_array($dto->transitions ?? null) ? $dto->transitions : [];
    $split = app(StateDiagramMermaidGenerator::class)->splitTerminals($transitions);
@endphp

<div class="space-y-8">
    <section class="space-y-3">
        <h4 class="text-sm font-semibold text-foreground">{{ __('ui.metadata') }}</h4>
        <x-details-view
            model="{{ $modelName }}"
            :dto="$dto"
            :fields="$fields"
        />
    </section>

    @include('pages.state_flows.partials.transitions-editor', [
        'transitions' => $split['transitions'],
        'initialState' => $split['initial'],
        'finalStates' => implode(', ', $split['finals']),
        'bodyOnly' => true,
        'editable' => false,
        'autoRender' => true,
        'flowTitle' => $dto->title,
        'showTitleField' => true,
    ])
</div>
