@php
    // Canonical defaults live in ui_form_field_layout_vars().
    // Blade @include cannot export locals to the parent control view — FormBuilder
    // merges the same helper output when rendering bs* components. Controls also
    // extract() these in-scope after this include as a safety net.
    extract(ui_form_field_layout_vars((string) ($name ?? ''), $attributes ?? []), EXTR_SKIP);
@endphp
