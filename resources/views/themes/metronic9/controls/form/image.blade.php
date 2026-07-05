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
        <div class="flex flex-col lg:flex-row lg:items-start gap-2.5 mb-5">
            <label class="lg:w-1/4 text-sm font-medium text-foreground lg:pt-2.5">{{ $labelText }}</label>
            <div class="lg:flex-1">
                <img src="{{ $src }}" alt="{{ $labelText }}" class="rounded-lg size-[100px] object-cover border border-border" />
            </div>
        </div>
    @else
        <div class="flex flex-col gap-1 mb-5">
            <label class="text-sm font-medium text-foreground">{{ $labelText }}</label>
            <img src="{{ $src }}" alt="{{ $labelText }}" class="rounded-lg size-[100px] object-cover border border-border" />
        </div>
    @endif
@endif
