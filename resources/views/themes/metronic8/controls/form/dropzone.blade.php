@include(ui_form_view('_vars'))

@if ($horizontal)
    <div class="row mb-6">
        <label class="col-lg-4 col-form-label fw-semibold fs-6">{{ $labelText }}</label>
        <div class="col-lg-8 fv-row">
            <div class="dropzone" id="dropzone_{{ $name }}">
                <div class="dz-message needsclick">
                    <i class="bi bi-file-earmark-arrow-up text-primary fs-3x"></i>
                    <div class="ms-4">
                        <h3 class="fs-5 fw-bolder text-gray-900 mb-1">Drop files here or click to upload.</h3>
                        <span class="fs-7 fw-bold text-gray-400">Upload up to 10 files</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="mb-6">
        <label class="form-label fw-semibold fs-6">{{ $labelText }}</label>
        <div class="dropzone" id="dropzone_{{ $name }}">
            <div class="dz-message needsclick">
                <i class="bi bi-file-earmark-arrow-up text-primary fs-3x"></i>
                <div class="ms-4">
                    <h3 class="fs-5 fw-bolder text-gray-900 mb-1">Drop files here or click to upload.</h3>
                    <span class="fs-7 fw-bold text-gray-400">Upload up to 10 files</span>
                </div>
            </div>
        </div>
    </div>
@endif
