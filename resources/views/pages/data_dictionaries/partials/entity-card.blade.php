@php
    $entity = is_array($entity ?? null) ? $entity : [];
    $entityIndex = $entityIndex ?? 0;
    $editable = $editable ?? true;
    $types = $types ?? [];
    $fields = is_array($entity['fields'] ?? null) ? $entity['fields'] : [];
@endphp

<div class="border border-border rounded-lg p-4 space-y-3" data-entity-card>
    <div class="grid grid-cols-12 gap-3">
        <div class="col-span-12 md:col-span-4">
            <label class="text-xs text-muted-foreground">{{ __('ui.entity_name') }}</label>
            @if ($editable)
                <input type="text" class="kt-input" data-field="name" name="entities[{{ $entityIndex }}][name]" value="{{ $entity['name'] ?? '' }}" autocomplete="off">
            @else
                <div class="text-sm font-medium" data-field="name" data-value="{{ $entity['name'] ?? '' }}">{{ $entity['name'] ?? '' }}</div>
            @endif
        </div>
        <div class="col-span-12 md:col-span-8">
            <label class="text-xs text-muted-foreground">{{ __('ui.entity_meaning') }}</label>
            @if ($editable)
                <input type="text" class="kt-input" data-field="meaning" name="entities[{{ $entityIndex }}][meaning]" value="{{ $entity['meaning'] ?? '' }}" autocomplete="off">
            @else
                <div class="text-sm" data-field="meaning" data-value="{{ $entity['meaning'] ?? '' }}">{{ $entity['meaning'] ?? '' }}</div>
            @endif
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="kt-table table-auto w-full" data-fields-table>
            <thead>
                <tr>
                    <th>{{ __('ui.field_name') }}</th>
                    <th>{{ __('ui.field_meaning') }}</th>
                    <th class="w-32">{{ __('ui.field_type') }}</th>
                    <th class="w-16">{{ __('ui.field_pk') }}</th>
                    <th>{{ __('ui.field_references') }}</th>
                    <th class="w-20">{{ __('ui.field_may_set') }}</th>
                    <th class="w-20">{{ __('ui.field_may_see') }}</th>
                    @if ($editable)
                        <th class="w-24">{{ __('ui.actions') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($fields as $fieldIndex => $field)
                    @include('pages.data_dictionaries.partials.field-row', [
                        'field' => $field,
                        'entityIndex' => $entityIndex,
                        'fieldIndex' => $fieldIndex,
                        'editable' => $editable,
                        'types' => $types,
                    ])
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($editable)
        <div class="flex justify-between">
            <button type="button" class="kt-btn kt-btn-sm kt-btn-ghost text-danger" data-remove-entity>
                {{ __('ui.remove_entity') }}
            </button>
            <button type="button" class="kt-btn kt-btn-sm kt-btn-secondary" data-add-field>
                {{ __('ui.add_field') }}
            </button>
        </div>
    @endif
</div>
