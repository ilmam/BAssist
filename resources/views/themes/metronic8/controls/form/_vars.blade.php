@php
    use App\Helpers\Ui;

    $attributes = $attributes ?? [];
    $horizontal = Ui::keyset($attributes, 'layout') === null || ($attributes['layout'] ?? 'h') === 'h';
    $labelText = Ui::prettify(__('ui.'.$name));
@endphp
