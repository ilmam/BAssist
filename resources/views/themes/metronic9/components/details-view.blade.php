@php
    use App\Helpers\Ui;
@endphp

<div @class([
    'grid gap-x-6 gap-y-4',
    'grid-cols-1' => $columns <= 1,
    'grid-cols-1 md:grid-cols-2' => $columns >= 2,
])>
    @foreach ($fields as $name => $value)
        @php
            $display = is_bool($value)
                ? ($value ? __('ui.yes') : __('ui.no'))
                : trim((string) ($value ?? ''));
            $isEmpty = $display === '';
            $isMultiline = ! $isEmpty && str_contains($display, "\n");
        @endphp
        <div class="kt-form-item">
            <label class="kt-form-label">{{ Ui::fieldLabel((string) $name) }}</label>
            <div
                class="kt-input-view{{ $isMultiline ? ' kt-input-view--multiline' : '' }}"
                aria-readonly="true"
            >@if ($isEmpty)<span class="text-muted-foreground">—</span>@else{{ $display }}@endif</div>
        </div>
    @endforeach
</div>
