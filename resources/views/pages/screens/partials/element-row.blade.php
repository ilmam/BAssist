{{-- One read-only element row (details page). The editor is the tree in elements-designer. --}}
@php
    $kind = (string) ($row['kind'] ?? 'label');
    $label = (string) ($row['label'] ?? '');
    $hint = $row['row'] ?? null;
    $requirementId = $row['functional_requirement_id'] ?? null;
    $requirementLabel = $requirementId !== null ? ($requirements[$requirementId] ?? '#'.$requirementId) : '';
    $depth = (int) ($depth ?? 0);
@endphp

<tr data-element-row data-kind="{{ $kind }}" data-label="{{ mb_strtolower($label) }}">
    <td class="text-muted-foreground">{{ is_numeric($index) ? $index + 1 : '' }}</td>
    <td>{{ $hint !== null ? $hint : '' }}</td>
    <td>{{ \App\Support\ScreenElementKind::label($kind) }}</td>
    <td style="padding-inline-start: {{ 0.75 + $depth * 1.25 }}rem">@if ($depth > 0)<span class="text-muted-foreground">↳ </span>@endif{{ $label }}</td>
    <td>{{ $requirementLabel }}</td>
</tr>
