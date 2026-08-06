@php
    /** @var list<array{name: string, label: string, empty_label: string, value: string, options: list<array{value: string, label: string}>}> $fields */
    $fields = $fields ?? [];
@endphp

@foreach ($fields as $field)
    <div class="list-filter-panel__field">
        <label for="list_filter_{{ $field['name'] }}" class="text-sm text-muted-foreground">
            {{ $field['label'] }}
        </label>
        <select
            name="{{ $field['name'] }}"
            id="list_filter_{{ $field['name'] }}"
            class="kt-select"
            data-kt-select="true"
        >
            <option value="">{{ $field['empty_label'] }}</option>
            @foreach ($field['options'] as $option)
                <option value="{{ $option['value'] }}" @selected((string) $field['value'] === (string) $option['value'])>
                    {{ $option['label'] }}
                </option>
            @endforeach
        </select>
    </div>
@endforeach
