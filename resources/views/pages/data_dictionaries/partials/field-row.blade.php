@php
    $field = is_array($field ?? null) ? $field : [];
    $entityIndex = $entityIndex ?? 0;
    $fieldIndex = $fieldIndex ?? 0;
    $editable = $editable ?? true;
    $types = $types ?? [];
    $type = $field['type'] ?? '';
    $prefix = "entities[{$entityIndex}][fields][{$fieldIndex}]";
@endphp

<tr data-field-row>
    <td>
        @if ($editable)
            <input type="text" class="kt-input" data-field="name" name="{{ $prefix }}[name]" value="{{ $field['name'] ?? '' }}" autocomplete="off">
        @else
            <span class="text-sm" data-field="name" data-value="{{ $field['name'] ?? '' }}">{{ $field['name'] ?? '' }}</span>
        @endif
    </td>
    <td>
        @if ($editable)
            <input type="text" class="kt-input" data-field="meaning" name="{{ $prefix }}[meaning]" value="{{ $field['meaning'] ?? '' }}" autocomplete="off">
        @else
            <span class="text-sm" data-field="meaning" data-value="{{ $field['meaning'] ?? '' }}">{{ $field['meaning'] ?? '' }}</span>
        @endif
    </td>
    <td>
        @if ($editable)
            <select class="kt-select" data-field="type" name="{{ $prefix }}[type]">
                <option value="" @selected($type === '' || $type === 'auto')>{{ __('ui.field_type_auto') }}</option>
                @foreach ($types as $option)
                    <option value="{{ $option }}" @selected($type === $option)>{{ $option }}</option>
                @endforeach
            </select>
        @else
            <span class="text-sm" data-field="type" data-value="{{ $type }}">{{ $type !== '' ? $type : __('ui.field_type_auto') }}</span>
        @endif
    </td>
    <td>
        @if ($editable)
            <input type="hidden" data-field="is_pk" name="{{ $prefix }}[is_pk]" value="0">
            <input type="checkbox" data-field="is_pk" name="{{ $prefix }}[is_pk]" value="1" @checked(! empty($field['is_pk']))>
        @else
            <span data-field="is_pk" data-value="{{ ! empty($field['is_pk']) ? '1' : '0' }}">{{ ! empty($field['is_pk']) ? __('ui.yes') : '' }}</span>
        @endif
    </td>
    <td>
        @if ($editable)
            <input type="text" class="kt-input" data-field="references" name="{{ $prefix }}[references]" value="{{ $field['references'] ?? '' }}" placeholder="{{ __('ui.field_references_placeholder') }}" autocomplete="off">
        @else
            <span class="text-sm" data-field="references" data-value="{{ $field['references'] ?? '' }}">{{ $field['references'] ?? '' }}</span>
        @endif
    </td>
    <td>
        @if ($editable)
            <input type="hidden" data-field="business_may_set" name="{{ $prefix }}[business_may_set]" value="0">
            <input type="checkbox" data-field="business_may_set" name="{{ $prefix }}[business_may_set]" value="1" @checked(($field['business_may_set'] ?? true) !== false)>
        @else
            <span data-field="business_may_set" data-value="{{ ($field['business_may_set'] ?? true) !== false ? '1' : '0' }}">{{ ($field['business_may_set'] ?? true) !== false ? __('ui.yes') : '' }}</span>
        @endif
    </td>
    <td>
        @if ($editable)
            <input type="hidden" data-field="business_may_see" name="{{ $prefix }}[business_may_see]" value="0">
            <input type="checkbox" data-field="business_may_see" name="{{ $prefix }}[business_may_see]" value="1" @checked(($field['business_may_see'] ?? true) !== false)>
        @else
            <span data-field="business_may_see" data-value="{{ ($field['business_may_see'] ?? true) !== false ? '1' : '0' }}">{{ ($field['business_may_see'] ?? true) !== false ? __('ui.yes') : '' }}</span>
        @endif
    </td>
    @if ($editable)
        <td>
            <button type="button" class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon" data-remove-field title="{{ __('ui.remove_field') }}" aria-label="{{ __('ui.remove_field') }}">
                <i class="ki-filled ki-trash"></i>
            </button>
        </td>
    @endif
</tr>
