{{-- One element row. Index "__i__" is the placeholder used by the editor's row template. --}}
@php
    $editable = (bool) ($editable ?? false);
    $kind = (string) ($row['kind'] ?? 'label');
    $label = (string) ($row['label'] ?? '');
    $hint = $row['row'] ?? null;
    $requirementId = $row['functional_requirement_id'] ?? null;
    $requirementLabel = $requirementId !== null ? ($requirements[$requirementId] ?? '#'.$requirementId) : '';
    $key = (string) ($row['key'] ?? ($row['id'] ?? ''));
    $parentKey = (string) ($row['parent_key'] ?? '');
    $depth = (int) ($depth ?? 0);
    $isContainer = \App\Support\ScreenElementKind::isContainer($kind);
@endphp

<tr data-element-row data-kind="{{ $kind }}" data-label="{{ mb_strtolower($label) }}" @if ($isContainer) data-container @endif>
    <td class="text-muted-foreground" data-row-number>{{ is_numeric($index) ? $index + 1 : '' }}</td>

    @if ($editable)
        <td>
            @if (! empty($row['id']))
                <input type="hidden" name="elements[{{ $index }}][id]" value="{{ $row['id'] }}">
            @endif
            <input type="hidden" name="elements[{{ $index }}][key]" data-field="key" value="{{ $key }}">
            <input type="number" min="0" class="kt-input" name="elements[{{ $index }}][row]" data-field="row" value="{{ $hint }}" placeholder="–" title="{{ __('ui.screen_element_row_help') }}">
        </td>
        <td>
            <select class="kt-select" name="elements[{{ $index }}][kind]" data-field="kind">
                @foreach ($kinds as $value => $name)
                    <option value="{{ $value }}" @selected($kind === $value)>{{ $name }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <select class="kt-select" name="elements[{{ $index }}][parent_key]" data-field="parent_key" data-selected="{{ $parentKey }}" title="{{ __('ui.screen_element_inside_help') }}">
                <option value="">—</option>
            </select>
        </td>
        <td data-label-cell>
            <input type="text" class="kt-input" name="elements[{{ $index }}][label]" data-field="label" value="{{ $label }}" maxlength="255" autocomplete="off">
        </td>
        <td>
            <select class="kt-select" name="elements[{{ $index }}][functional_requirement_id]" data-field="functional_requirement_id">
                <option value="">—</option>
                @foreach ($requirements as $id => $name)
                    <option value="{{ $id }}" @selected((string) $requirementId === (string) $id)>{{ $name }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <div class="flex gap-1">
                <button type="button" class="kt-btn kt-btn-outline kt-btn-icon kt-btn-sm" data-row-up title="{{ __('ui.move_up') }}"><i class="ki-filled ki-arrow-up"></i></button>
                <button type="button" class="kt-btn kt-btn-outline kt-btn-icon kt-btn-sm" data-row-down title="{{ __('ui.move_down') }}"><i class="ki-filled ki-arrow-down"></i></button>
                <button type="button" class="kt-btn kt-btn-outline kt-btn-icon kt-btn-sm" data-row-remove title="{{ __('ui.delete') }}"><i class="ki-filled ki-trash"></i></button>
            </div>
        </td>
    @else
        <td>{{ $hint !== null ? $hint : '' }}</td>
        <td>{{ \App\Support\ScreenElementKind::label($kind) }}</td>
        <td style="padding-inline-start: {{ 0.75 + $depth * 1.25 }}rem">@if ($depth > 0)<span class="text-muted-foreground">↳ </span>@endif{{ $label }}</td>
        <td>{{ $requirementLabel }}</td>
    @endif
</tr>
