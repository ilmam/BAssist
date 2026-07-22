@php
    use App\Helpers\Ui;
@endphp

@foreach ($fields as $name => $value)
    <div class="row mb-7">
        <label class="col-lg-4 fw-bold text-gray-800">{{ Ui::fieldLabel((string) $name) }}</label>
        <div class="col-lg-8">
            <span class="fw-normal fs-6 text-gray-800">{{ $value }}</span>
        </div>
    </div>
@endforeach
