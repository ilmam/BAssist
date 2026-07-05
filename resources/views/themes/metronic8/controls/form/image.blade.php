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
        <div class="row mb-6">
            <label class="col-lg-4 col-form-label fw-semibold fs-6">{{ $labelText }}</label>
            <div class="col-lg-8 fv-row">
                <div class="bgi-no-repeat bgi-size-cover bgi-position-center rounded h-100px w-100px" style="background-image: url({{ $src }})"></div>
            </div>
        </div>
    @else
        <div class="mb-6">
            <label class="form-label fw-semibold fs-6">{{ $labelText }}</label>
            <div class="bgi-no-repeat bgi-size-cover bgi-position-center rounded h-100px w-100px" style="background-image: url({{ $src }})"></div>
        </div>
    @endif
@endif
