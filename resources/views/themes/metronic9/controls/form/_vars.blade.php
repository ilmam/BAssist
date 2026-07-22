@php
    use App\Helpers\Ui;

    $attributes = $attributes ?? [];
    // Next.js Metronic forms are stacked (label above control). Opt into
    // horizontal with attributes.layout = 'h'.
    $horizontal = ($attributes['layout'] ?? 'v') === 'h';
    $labelText = Ui::fieldLabel((string) $name);
@endphp
