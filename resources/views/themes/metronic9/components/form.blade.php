@php
    use App\Attributes\Form as FormAttribute;
    use App\Facades\Form;
    $formRoute = in_array($verb, ['POST', 'post'], true)
        ? ['route' => $route]
        : ['route' => [$route, $dto->id]];
    $formOpenOptions = array_merge($formRoute, ['id' => $id, 'files' => true, 'method' => 'post']);

    if ($inModal) {
        $formOpenOptions['attributes'] = [
            'data-modal-form' => 'true',
        ];
    }

    // Compact density is reserved for quick-create forms only.
    // Regular full-page and modal create/edit forms stay comfortable (default).
    if ($quickCreate) {
        $formOpenOptions['attributes'] = array_merge(
            $formOpenOptions['attributes'] ?? [],
            [
                'data-modal-form' => 'true',
                'data-quick-create-form' => 'true',
                'data-form-density' => 'compact',
            ]
        );
    }

    // All forms (quick-create, modal, and full page) use a 12-col grid so
    // narrow fields default to half width; compact spacing (quick-create
    // only) is owned by .form-fields-grid under [data-form-density=compact]
    // in ui-layout.css (do not also apply gap-y-* — that stacks with
    // .kt-form-item margins).
    $fieldsWrapperClass = 'form-fields-grid grid grid-cols-12';

    // Keep DTO order: consecutive `section: traceability` fields share one box.
    // project_id is context-scoped (sticky project) — never shown as a visible control.
    $fieldChunks = [];
    $hasProjectField = false;
    foreach ($fieldsArray as $name => $field) {
        $fieldName = is_numeric($name) ? $field : $name;
        if ($fieldName === 'project_id') {
            $hasProjectField = true;
            continue;
        }

        $section = is_array($field) ? (string) ($field['section'] ?? '') : '';
        $isTrace = $section === FormAttribute::SECTION_TRACEABILITY;
        $chunkKey = $isTrace ? 'traceability' : 'main';

        $last = $fieldChunks === [] ? null : $fieldChunks[array_key_last($fieldChunks)];
        if ($last === null || $last['type'] !== $chunkKey) {
            $fieldChunks[] = ['type' => $chunkKey, 'fields' => []];
        }

        $fieldChunks[array_key_last($fieldChunks)]['fields'][$fieldName] = $field;
    }
@endphp

{{ Form::open($formOpenOptions) }}
    <div class="{{ $inModal ? '' : 'kt-card-body border-t border-border p-5 lg:p-7.5' }} {{ count($fieldChunks) > 1 ? 'form-body-sections' : '' }}" data-ui-container>
        @if (! in_array($verb, ['POST', 'post'], true))
            @method($verb)
        @endif

        @if ($dto->id ?? null)
            {{ Form::hidden('id', $dto->id) }}
        @endif

        @if ($hasProjectField || (isset($dto->project_id) && $dto->project_id))
            {{ Form::hidden('project_id', $dto->project_id ?? '') }}
        @endif

        @if ($quickCreate)
            @foreach ($hiddenDefaults as $hiddenName => $hiddenValue)
                @continue($hiddenName === 'project_id' && ($hasProjectField || (isset($dto->project_id) && $dto->project_id)))
                {{ Form::hidden($hiddenName, $hiddenValue) }}
            @endforeach
        @endif

        @foreach ($fieldChunks as $chunk)
            @if ($chunk['type'] === 'traceability')
                <section class="form-section-box form-section-box--traceability">
                    <div class="{{ $fieldsWrapperClass }}">
                        @include('pages.partials.form-field-cells', [
                            'fields' => $chunk['fields'],
                            'dto' => $dto,
                            'quickCreate' => $quickCreate,
                        ])
                    </div>
                </section>
            @else
                <div class="{{ $fieldsWrapperClass }}">
                    @include('pages.partials.form-field-cells', [
                        'fields' => $chunk['fields'],
                        'dto' => $dto,
                        'quickCreate' => $quickCreate,
                    ])
                </div>
            @endif
        @endforeach
    </div>
    <div class="{{ $inModal ? 'flex justify-end gap-2.5 mt-4' : 'kt-card-footer flex justify-end gap-2.5 border-t border-border p-5 lg:p-7.5' }}">
        @if ($quickCreate)
            <x-button type="button" color="outline" class="hidden" data-qc-cancel-edit>{{ __('ui.cancel_edit') }}</x-button>
            <x-button type="button" color="outline" data-kt-modal-dismiss="true">{{ __('ui.done') }}</x-button>
            <x-button type="submit" color="primary" data-qc-submit>{{ __('ui.add_another') }}</x-button>
        @elseif ($inModal)
            <x-button type="button" color="outline" data-kt-modal-dismiss="true">Cancel</x-button>
            <x-button type="submit" color="primary">Save</x-button>
        @else
            <x-button type="link" href="{{ $cancelRoute }}" color="outline">Cancel</x-button>
            <x-button type="submit" color="primary">Save</x-button>
        @endif
    </div>
{{ Form::close() }}
