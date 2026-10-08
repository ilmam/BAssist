{{-- Read-only element list for the details page. Editing happens in the screen's edit form. --}}
@php
    $requirements = $screen->functionalRequirements
        ->mapWithKeys(fn ($fr) => [$fr->id => $fr->code.' — '.$fr->title])
        ->all();

    foreach ($screen->screenElements as $element) {
        if ($element->functionalRequirement) {
            $requirements[$element->functionalRequirement->id] = $element->functionalRequirement->code.' — '.$element->functionalRequirement->title;
        }
    }

    $rows = $screen->screenElements->map(fn ($e) => [
        'id' => $e->id,
        'key' => (string) $e->id,
        'parent_key' => $e->parent_id !== null ? (string) $e->parent_id : null,
        'row' => $e->row,
        'kind' => $e->kind,
        'label' => $e->label,
        'functional_requirement_id' => $e->functional_requirement_id,
    ])->all();
    $listId = 'screen-elements-'.uniqid();
@endphp

<section class="space-y-4" id="{{ $listId }}">
    <div class="form-section-intro space-y-1">
        <h3 class="text-base font-semibold text-foreground">{{ __('ui.screen_elements') }}</h3>
        <p class="text-sm text-muted-foreground">{{ __('ui.screen_elements_help') }}</p>
    </div>

    @include('pages.screens.partials.elements-table', [
        'elements' => $rows,
        'editable' => false,
        'requirements' => $requirements,
    ])
</section>

<script>
    (function () {
        var host = document.getElementById(@json($listId));

        function start() { window.ScreenDesigner.initList(host.querySelector('[data-element-list]')); }

        if (window.ScreenDesigner) { start(); return; }

        var tag = document.createElement('script');
        tag.src = @json(asset('js/screen-designer.js'));
        tag.onload = start;
        document.head.appendChild(tag);
    })();
</script>
