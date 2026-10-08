{{--
    Editable element rows with a live preview. The preview is drawn from what is in the
    form right now (server assembles the Salt, the browser draws it); nothing is saved.
--}}
@php
    $requirements = $projectId > 0
        ? \App\Models\FunctionalRequirement::query()
            ->where('project_id', $projectId)
            ->orderBy('number')
            ->get(['id', 'number', 'title'])
            ->mapWithKeys(fn ($fr) => [$fr->id => $fr->code.' — '.$fr->title])
            ->all()
        : [];
    $kinds = \App\Support\ScreenElementKind::selectOptions();
    $designerId = 'screen-designer-'.uniqid();
@endphp

<link rel="stylesheet" href="{{ asset('css/salt-wireframe.css') }}">

<div id="{{ $designerId }}" class="space-y-5" data-screen-designer data-preview-url="{{ $previewUrl }}">
    {{-- Tells the server the rows were submitted, so removing every row removes them all. --}}
    <input type="hidden" name="elements[_present]" value="1">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="form-section-intro space-y-1">
            <h3 class="text-base font-semibold text-foreground">{{ __('ui.screen_elements') }}</h3>
            <p class="text-sm text-muted-foreground">{{ __('ui.screen_elements_edit_help') }}</p>
        </div>
        <button type="button" class="kt-btn kt-btn-primary" data-row-add>{{ __('ui.add_screen_element') }}</button>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">
        <div>
            @include('pages.screens.partials.elements-table', [
                'elements' => $elements,
                'editable' => true,
                'requirements' => $requirements,
            ])
        </div>

        <div class="xl:sticky xl:top-4 space-y-2">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-base font-semibold text-foreground">{{ __('ui.screen_preview') }}</h3>
                <span class="text-xs text-muted-foreground" data-preview-status>{{ __('ui.screen_preview_help') }}</span>
            </div>
            <div class="p-4 bg-white rounded border overflow-auto" style="min-height: 10rem;" data-preview></div>
        </div>
    </div>

    <template data-row-template>
        @include('pages.screens.partials.element-row', [
            'index' => '__i__',
            'row' => ['kind' => 'label'],
            'editable' => true,
            'kinds' => $kinds,
            'requirements' => $requirements,
        ])
    </template>
</div>

<script>
    (function () {
        var root = document.getElementById(@json($designerId));

        // The page may inject scripts after this one runs, so load in order and start when ready.
        function load(urls, done) {
            if (!urls.length) { done(); return; }
            var url = urls.shift();
            var tag = document.createElement('script');
            tag.src = url;
            tag.onload = function () { load(urls, done); };
            tag.onerror = function () { root.querySelector('[data-preview]').textContent = 'Could not load ' + url; };
            document.head.appendChild(tag);
        }

        var needed = [];
        if (!window.SaltWireframe) needed.push(@json(asset('js/salt-wireframe.js')));
        if (!window.ScreenDesigner) needed.push(@json(asset('js/screen-designer.js')));
        load(needed, function () { window.ScreenDesigner.init(root); });
    })();
</script>
