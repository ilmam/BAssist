@include(ui_form_view('_vars'))

@php
    $typeaheadClass = Ui::keyset($attributes, 'autocomplete') !== null ? 'typeahead' : '';
    $inputClass = 'kt-input';
    $fieldHelp = (string) ($attributes['data-field-help'] ?? $attributes['help'] ?? '');
    unset($attributes['data-field-help'], $attributes['help']);
@endphp

@if ($horizontal)
    <div class="flex flex-col lg:flex-row lg:items-start gap-2.5 {{ $typeaheadClass }}">
        <label class="kt-form-label lg:w-1/4 lg:pt-2.5" for="{{ $name }}">{{ $labelText }}</label>
        <div class="lg:flex-1">
            {{ Form::text($name, $value, array_merge(['class' => $inputClass], $attributes)) }}
            @if ($fieldHelp !== '')
                <p class="field-help">{{ $fieldHelp }}</p>
            @endif
        </div>
    </div>
@else
    <div class="kt-form-item {{ $typeaheadClass }}">
        <label class="kt-form-label" for="{{ $name }}">{{ $labelText }}</label>
        {{ Form::text($name, $value, array_merge(['class' => $inputClass], $attributes)) }}
        @if ($fieldHelp !== '')
            <p class="field-help">{{ $fieldHelp }}</p>
        @endif
    </div>
@endif

@if (Ui::keyset($attributes, 'autocomplete') !== null)
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var {{ $name }}_engine = new Bloodhound({
                datumTokenizer: function(e) { return e.tokens; },
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                remote: { url: '/typeahead/tree/%QUERY', wildcard: '%QUERY' }
            });
            {{ $name }}_engine.initialize();
            $('#{{ $name }}').typeahead({ hint: false, minLength: 2 }, {
                name: '{{ $name }}',
                displayKey: 'value',
                highlight: true,
                limit: 6,
                source: {{ $name }}_engine.ttAdapter()
            });
        });
    </script>
    @endpush
@endif
