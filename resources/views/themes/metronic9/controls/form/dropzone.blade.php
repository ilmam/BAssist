@include(ui_form_view('_vars'))

@php
    extract(ui_form_field_layout_vars((string) ($name ?? ''), $attributes ?? []), EXTR_SKIP);
@endphp

@if ($horizontal)
    <div class="{{ $fieldRowClass }}">
        <label class="kt-form-label lg:w-1/4 lg:pt-2.5">{{ $labelText }}</label>
        <div class="lg:flex-1">
            <div class="dropzone border border-dashed border-border rounded-lg p-5 bg-muted/30" id="dropzone_{{ $name }}">
                <div class="dz-message needsclick text-center">
                    <i class="ki-filled ki-file-up text-2xl text-primary"></i>
                    <p class="text-sm font-medium text-foreground mt-2">Drop files here or click to upload</p>
                    <p class="text-xs text-secondary-foreground">Upload up to 10 files</p>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="{{ $fieldStackClass }}">
        <label class="kt-form-label">{{ $labelText }}</label>
        <div class="dropzone border border-dashed border-border rounded-lg p-5 bg-muted/30" id="dropzone_{{ $name }}">
            <div class="dz-message needsclick text-center">
                <i class="ki-filled ki-file-up text-2xl text-primary"></i>
                <p class="text-sm font-medium text-foreground mt-2">Drop files here or click to upload</p>
                <p class="text-xs text-secondary-foreground">Upload up to 10 files</p>
            </div>
        </div>
    </div>
@endif

@once
    @push('styles')
        <link href="{{ ui_asset('vendors/dropzone/dropzone.css') }}" rel="stylesheet" />
    @endpush
    @push('scripts')
        <script src="{{ ui_asset('vendors/dropzone/dropzone.js') }}"></script>
    @endpush
@endonce
