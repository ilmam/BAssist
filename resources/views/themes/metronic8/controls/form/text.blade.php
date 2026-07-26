@include(ui_form_view('_vars'))

@php
    $typeaheadClass = Ui::keyset($attributes, 'autocomplete') !== null ? 'typeahead' : '';
    $fieldHelp = (string) ($attributes['data-field-help'] ?? $attributes['help'] ?? '');
    unset($attributes['data-field-help'], $attributes['help']);
@endphp

@if ($horizontal)
    <div class="row mb-6 {{ $typeaheadClass }}">
        <label class="col-lg-4 col-form-label fw-semibold fs-6" for="{{ $name }}">{{ $labelText }}</label>
        <div class="col-lg-8 fv-row">
            {{ Form::text($name, $value, array_merge(['class' => 'form-control form-control-solid'], $attributes)) }}
            @if ($fieldHelp !== '')
                <p class="field-help text-muted fs-7 mt-1 mb-0">{{ $fieldHelp }}</p>
            @endif
        </div>
    </div>
@else
    <div class="mb-6 {{ $typeaheadClass }}">
        <label class="form-label fw-semibold fs-6" for="{{ $name }}">{{ $labelText }}</label>
        {{ Form::text($name, $value, array_merge(['class' => 'form-control form-control-solid'], $attributes)) }}
        @if ($fieldHelp !== '')
            <p class="field-help text-muted fs-7 mt-1 mb-0">{{ $fieldHelp }}</p>
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
