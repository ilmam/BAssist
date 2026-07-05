@include(ui_form_view('_vars'))

@php
    $list = $list ?? [];
    $inline = Ui::keyset($attributes, 'inline') !== null && $attributes['inline'] == true;
    $listClass = $inline ? 'd-flex flex-wrap gap-5' : 'd-flex flex-column gap-3';
@endphp

@if ($horizontal)
    <div class="row mb-6">
        <label class="col-lg-4 col-form-label fw-semibold fs-6">{{ $labelText }}</label>
        <div class="col-lg-8 fv-row">
            <div class="{{ $listClass }}">
                @foreach ($list as $id => $text)
                    <label class="form-check form-check-custom form-check-solid">
                        {{ Form::radio($name, $id, $value == $id, ['class' => 'form-check-input']) }}
                        <span class="form-check-label">{{ $text }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    </div>
@else
    <div class="mb-6">
        <label class="form-label fw-semibold fs-6 d-block">{{ $labelText }}</label>
        <div class="{{ $listClass }}">
            @foreach ($list as $id => $text)
                <label class="form-check form-check-custom form-check-solid">
                    {{ Form::radio($name, $id, $value == $id, ['class' => 'form-check-input']) }}
                    <span class="form-check-label">{{ $text }}</span>
                </label>
            @endforeach
        </div>
    </div>
@endif
