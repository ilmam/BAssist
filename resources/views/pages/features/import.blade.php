@extends(ui_layout())

@section('main')
    <x-form-card :title="__('ui.import_feature_file')">
        <x-slot:toolbar>
            <x-button type="link" href="{{ $backUrl }}" icon="arrow-left" iconOnly="true" color="light" activeColor="primary"></x-button>
        </x-slot>

        <form
            id="feature-import-form"
            action="{{ $previewUrl }}"
            method="post"
            enctype="multipart/form-data"
            class="kt-card-body border-t border-border p-5 lg:p-7.5 space-y-6"
        >
            @csrf

            <div class="space-y-2">
                <p class="text-sm text-muted-foreground">
                    {{ __('ui.feature_import_replace_help', [
                        'code' => $feature->code ?: ('#'.$feature->id),
                        'title' => $feature->title,
                    ]) }}
                </p>
                <p class="text-sm text-muted-foreground">{{ __('ui.feature_import_preserve_help') }}</p>
            </div>

            @if ($errors->any())
                <div class="kt-alert kt-alert-destructive">
                    <ul class="list-disc ms-4 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="kt-form-item">
                <label class="kt-form-label" for="feature_file">{{ __('ui.feature_import_upload_label') }}</label>
                <input
                    id="feature_file"
                    name="feature_file"
                    type="file"
                    accept=".feature,text/plain"
                    required
                    class="kt-input"
                >
                <p class="field-help">{{ __('ui.feature_import_upload_hint') }}</p>
            </div>
        </form>

        <div class="kt-card-footer flex justify-end gap-2.5 border-t border-border p-5 lg:p-7.5">
            <x-button type="link" href="{{ $backUrl }}" color="secondary">Cancel</x-button>
            <x-button type="submit" form="feature-import-form" color="primary">{{ __('ui.feature_import_review') }}</x-button>
        </div>
    </x-form-card>
@endsection
