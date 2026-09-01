@extends(ui_layout())

@section('main')
    <x-form-card :title="__('ui.import_feature_file')">
        <x-slot:toolbar>
            <x-button type="link" href="{{ $backUrl }}" icon="arrow-left" iconOnly="true" color="ghost" size="sm" activeColor="primary"></x-button>
        </x-slot>

        <form
            id="feature-import-form"
            action="{{ $previewUrl }}"
            method="post"
            enctype="multipart/form-data"
        >
            <x-form-card-body class="space-y-6">
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
                    <x-alert>
                        <ul class="list-disc ms-4 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </x-alert>
                @endif

                <div>
                    <label class="font-medium text-sm text-foreground" for="feature_file">{{ __('ui.feature_import_upload_label') }}</label>
                    <input
                        id="feature_file"
                        name="feature_file"
                        type="file"
                        accept=".feature,text/plain"
                        required
                        class="mt-1.5 block w-full text-sm"
                    >
                    <p class="field-help">{{ __('ui.feature_import_upload_hint') }}</p>
                </div>
            </x-form-card-body>

            <x-form-card-footer>
                <x-button type="link" href="{{ $backUrl }}" color="outline">Cancel</x-button>
                <x-button type="submit" color="primary">{{ __('ui.feature_import_review') }}</x-button>
            </x-form-card-footer>
        </form>
    </x-form-card>
@endsection
