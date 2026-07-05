@include(ui_form_view('_vars'))

@if ($horizontal)
    <div class="flex flex-col lg:flex-row lg:items-start gap-2.5 mb-5">
        <label class="lg:w-1/4 text-sm font-medium text-foreground lg:pt-2.5">{{ $labelText }}</label>
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
    <div class="flex flex-col gap-1 mb-5">
        <label class="text-sm font-medium text-foreground">{{ $labelText }}</label>
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
