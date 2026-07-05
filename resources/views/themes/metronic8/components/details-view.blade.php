@foreach ($fields as $name=>$value)
    @php
        $fieldName = is_numeric($name) ? $field : $name;
        $options = null;
        // dd($fields);
        // print_r($dto->{$fieldName});
    @endphp
    <div class="row mb-7">
        <!--begin::Label-->
        <label class="col-lg-4 fw-semibold text-muted">{{ $fieldName }}</label>
        <!--end::Label-->
        <!--begin::Col-->
        <div class="col-lg-8">
            <span class="fw-bold fs-6 text-gray-800">{{ $value }}</span>
        </div>
        <!--end::Col-->
    </div>
@endforeach
