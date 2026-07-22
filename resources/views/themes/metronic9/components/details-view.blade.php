@php
    use App\Helpers\Ui;
@endphp

@foreach ($fields as $name => $value)
    <div class="kt-form-item">
        <label class="kt-form-label font-semibold text-foreground">{{ Ui::fieldLabel((string) $name) }}</label>
        <span class="text-sm font-normal text-foreground">{{ $value }}</span>
    </div>
@endforeach
