@include(ui_form_view('_vars'))

@php
    use Illuminate\Support\Facades\Storage;

    if ($file != '' && Storage::exists($path.$file)) {
        $showImage = true;
        $src = Storage::url($path.$file);
    } elseif (Ui::keyset($attributes, 'default') !== null) {
        $showImage = true;
        $src = Storage::url($attributes['default']);
    } else {
        $src = '';
        $showImage = false;
    }
@endphp

@if ($showImage)
    @if ($horizontal)
        <div class="flex flex-col lg:flex-row lg:items-start gap-2.5">
            <label class="kt-form-label lg:w-1/4 lg:pt-2.5">{{ $labelText }}</label>
            <div class="lg:flex-1">
                <img src="{{ $src }}" alt="{{ $labelText }}" class="rounded-lg size-[100px] object-cover border border-border" />
            </div>
        </div>
    @else
        <div class="kt-form-item">
            <label class="kt-form-label">{{ $labelText }}</label>
            <img src="{{ $src }}" alt="{{ $labelText }}" class="rounded-lg size-[100px] object-cover border border-border" />
        </div>
    @endif
@endif
