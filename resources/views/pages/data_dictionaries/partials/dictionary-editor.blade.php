@php
    use App\Services\DataTypeInference;

    $entities = is_array($entities ?? null) ? $entities : [];
    $editable = $editable ?? true;
    $autoRender = $autoRender ?? ! $editable;
    $showDiagram = $showDiagram ?? true;
    $types = DataTypeInference::TYPES;

    if ($entities === []) {
        $entities = [[
            'name' => '',
            'meaning' => '',
            'fields' => [[
                'name' => '',
                'meaning' => '',
                'type' => '',
                'is_pk' => false,
                'references' => '',
                'business_may_set' => true,
                'business_may_see' => true,
            ]],
        ]];
    }

    foreach ($entities as $i => $entity) {
        if (! is_array($entity['fields'] ?? null) || $entity['fields'] === []) {
            $entities[$i]['fields'] = [[
                'name' => '',
                'meaning' => '',
                'type' => '',
                'is_pk' => false,
                'references' => '',
                'business_may_set' => true,
                'business_may_see' => true,
            ]];
        }
    }
@endphp

@once
    <style>
        /* Hard spacing and shading: do not rely on utility classes alone (they are not all compiled). */
        [data-data-dictionary-editor] [data-entities] {
            display: flex;
            flex-direction: column;
            gap: 1.75rem;
        }

        /* Each entity is its own shaded, outlined card with an accent edge; odd and even differ slightly. */
        [data-data-dictionary-editor] [data-entity-card] {
            --dd-tone: #64748b;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            padding: 1.1rem 1.25rem 1.25rem;
            border: 1px solid color-mix(in srgb, var(--dd-tone) 28%, var(--border, #e5e7eb));
            border-inline-start: 4px solid var(--dd-tone);
            border-radius: 0.6rem;
            background: color-mix(in srgb, var(--dd-tone) 6%, transparent);
        }
        [data-data-dictionary-editor] [data-entity-card]:nth-child(even) {
            --dd-tone: #2563eb;
        }

        /* The field table stays white so the fields read clearly against the shaded card. */
        [data-data-dictionary-editor] [data-entity-card] .overflow-x-auto {
            border: 1px solid var(--border, #e5e7eb);
            border-radius: 0.5rem;
            background: var(--background, #fff);
        }

        /* Name and meaning side by side on wide screens (the 12-column utility classes are not compiled). */
        [data-data-dictionary-editor] [data-entity-card] > .grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 0.75rem 1rem;
        }
        @media (min-width: 768px) {
            [data-data-dictionary-editor] [data-entity-card] > .grid {
                grid-template-columns: minmax(0, 1fr) minmax(0, 2fr);
            }
        }
        /* The children carry col-span-12 / md:col-span-4 / md:col-span-8 classes; let our two columns win. */
        [data-data-dictionary-editor] [data-entity-card] > .grid > * {
            grid-column: auto !important;
        }
        [data-data-dictionary-editor] [data-entity-card] > .grid label {
            display: block;
            margin-bottom: 0.25rem;
        }

        [data-data-dictionary-editor] [data-entity-card] > .flex {
            padding-top: 0.25rem;
        }

        [data-data-dictionary-editor] [data-add-entity] {
            margin-top: 1.25rem;
        }
    </style>
@endonce

<div
    data-data-dictionary-editor
    @if ($autoRender) data-auto-render="1" @endif
    class="space-y-5"
>
    <div>
        <h4 class="text-sm font-semibold text-foreground mb-1">{{ __('ui.data_dictionary_entities') }}</h4>
        <p class="text-xs text-muted-foreground mb-3">{{ __('ui.data_dictionary_entities_help') }}</p>

        <div class="space-y-4" data-entities>
            @foreach ($entities as $entityIndex => $entity)
                @include('pages.data_dictionaries.partials.entity-card', [
                    'entity' => $entity,
                    'entityIndex' => $entityIndex,
                    'editable' => $editable,
                    'types' => $types,
                ])
            @endforeach
        </div>

        @if ($editable)
            <button type="button" class="kt-btn kt-btn-primary mt-3" data-add-entity>
                {{ __('ui.add_entity') }}
            </button>
        @endif
    </div>

    @if ($editable)
        <template data-entity-template>
            @include('pages.data_dictionaries.partials.entity-card', [
                'entity' => [
                    'name' => '',
                    'meaning' => '',
                    'fields' => [[
                        'name' => '',
                        'meaning' => '',
                        'type' => '',
                        'is_pk' => false,
                        'references' => '',
                        'business_may_set' => true,
                        'business_may_see' => true,
                    ]],
                ],
                'entityIndex' => '__ENTITY__',
                'editable' => true,
                'types' => $types,
            ])
        </template>
        <template data-field-row-template>
            @include('pages.data_dictionaries.partials.field-row', [
                'field' => [
                    'name' => '',
                    'meaning' => '',
                    'type' => '',
                    'is_pk' => false,
                    'references' => '',
                    'business_may_set' => true,
                    'business_may_see' => true,
                ],
                'entityIndex' => '__ENTITY__',
                'fieldIndex' => '__FIELD__',
                'editable' => true,
                'types' => $types,
            ])
        </template>
    @endif

    @if ($showDiagram)
        <div class="space-y-3">
            <div>
                <h4 class="text-sm font-semibold text-foreground mb-1">{{ __('ui.diagram_preview') }}</h4>
                <p class="text-xs text-muted-foreground mb-3">{{ __('ui.data_dictionary_diagrams_help') }}</p>
            </div>

            <div class="flex items-center flex-wrap md:flex-nowrap lg:items-end justify-between border-b border-b-border gap-3 lg:gap-6">
                <div class="grid min-w-0 grow">
                    <div class="kt-scrollable-x-auto">
                        <div class="kt-menu gap-3" role="tablist" aria-label="{{ __('ui.diagram_preview') }}">
                            <div
                                class="kt-menu-item border-b-2 border-b-transparent kt-menu-item-active:border-b-primary kt-menu-item-here:border-b-primary active"
                                data-diagram-tab="conceptual"
                                data-help="{{ __('ui.conceptual_erd_help') }}"
                                role="tab"
                                aria-selected="true"
                            >
                                <button type="button" class="kt-menu-link gap-1.5 pb-2 lg:pb-4 px-2">
                                    <span class="kt-menu-title text-nowrap font-medium text-sm text-secondary-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-item-here:text-primary kt-menu-item-here:font-semibold kt-menu-item-show:text-primary kt-menu-link-hover:text-primary">
                                        {{ __('ui.conceptual_erd') }}
                                    </span>
                                </button>
                            </div>
                            <div
                                class="kt-menu-item border-b-2 border-b-transparent kt-menu-item-active:border-b-primary kt-menu-item-here:border-b-primary"
                                data-diagram-tab="design"
                                data-help="{{ __('ui.design_erd_help') }}"
                                role="tab"
                                aria-selected="false"
                            >
                                <button type="button" class="kt-menu-link gap-1.5 pb-2 lg:pb-4 px-2">
                                    <span class="kt-menu-title text-nowrap font-medium text-sm text-secondary-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-item-here:text-primary kt-menu-item-here:font-semibold kt-menu-item-show:text-primary kt-menu-link-hover:text-primary">
                                        {{ __('ui.design_erd') }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end grow lg:grow-0 lg:pb-4 gap-2.5 mb-3 lg:mb-0">
                    @if ($editable)
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-primary" data-preview-diagram>
                            {{ __('ui.preview_diagram') }}
                        </button>
                    @endif
                    @if (! empty($exportCsharpUrl))
                        <a href="{{ $exportCsharpUrl }}" class="kt-btn kt-btn-sm kt-btn-outline">{{ __('ui.export_ef_stub') }}</a>
                    @endif
                    @if (! empty($exportPhpUrl))
                        <a href="{{ $exportPhpUrl }}" class="kt-btn kt-btn-sm kt-btn-outline">{{ __('ui.export_php_stub') }}</a>
                    @endif
                </div>
            </div>

            <div class="space-y-3">
                <p class="text-xs text-muted-foreground m-0" data-diagram-tab-help>{{ __('ui.conceptual_erd_help') }}</p>

                <div class="border border-border rounded-lg p-4 bg-white" data-diagram-pane data-diagram-level="conceptual">
                    <div class="overflow-x-auto min-h-24">
                        <pre class="mermaid bassist-mermaid" data-mermaid-preview>@if ($editable){{ __('ui.preview_diagram_hint') }}@else{{ $mermaidConceptual ?? '' }}@endif</pre>
                    </div>
                    @include('pages.partials.mermaid-source', [
                        'source' => $mermaidConceptual ?? '',
                        'editorId' => 'data_dictionary_conceptual_'.uniqid(),
                        'summary' => __('ui.conceptual_erd_source'),
                    ])
                </div>
                <div class="border border-border rounded-lg p-4 bg-white" data-diagram-pane data-diagram-level="design" hidden>
                    <div class="overflow-x-auto min-h-24">
                        <pre class="mermaid bassist-mermaid" data-mermaid-preview>@if ($editable){{ __('ui.preview_diagram_hint') }}@else{{ $mermaid ?? '' }}@endif</pre>
                    </div>
                    @include('pages.partials.mermaid-source', [
                        'source' => $mermaid ?? '',
                        'editorId' => 'data_dictionary_design_'.uniqid(),
                        'summary' => __('ui.design_erd_source'),
                    ])
                </div>
            </div>
        </div>
    @endif
</div>
