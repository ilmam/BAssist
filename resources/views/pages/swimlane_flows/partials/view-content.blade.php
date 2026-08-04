@php
    $modelName = class_basename($model);
    $elements = is_array($dto->elements ?? null) ? $dto->elements : [];
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

    @include('pages.swimlane_flows.partials.elements-editor', [
        'elements' => $elements,
        'direction' => $dto->direction ?? 'TB',
        'editable' => false,
        'autoRender' => true,
        'flowTitle' => $dto->title,
        'showTitleField' => true,
        'satisfyOptions' => $satisfyOptions ?? [],
    ])
</div>
