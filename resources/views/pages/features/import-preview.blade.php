@extends(ui_layout())

@section('main')
    @php
        $warningLevelClass = [
            'warning' => 'kt-alert-light kt-alert-warning',
            'info' => 'kt-alert-light kt-alert-info',
            'danger' => 'kt-alert-light kt-alert-destructive',
        ];
    @endphp

    <x-form-card :title="__('ui.feature_import_confirm_title')">
        <x-slot:toolbar>
            <x-button type="link" href="{{ $backUrl }}" icon="arrow-left" iconOnly="true" color="ghost" size="sm" activeColor="primary"></x-button>
        </x-slot>

        <div class="kt-card-body border-t border-border p-5 lg:p-7.5 space-y-6">
            <div class="space-y-1">
                <p class="text-sm text-muted-foreground">
                    {{ __('ui.feature_import_confirm_help', [
                        'code' => $feature->code ?: ('#'.$feature->id),
                        'title' => $feature->title,
                    ]) }}
                </p>
                @if ($preview->filename !== '')
                    <p class="text-sm text-foreground">
                        <span class="text-muted-foreground">{{ __('ui.file') }}:</span>
                        {{ $preview->filename }}
                    </p>
                @endif
            </div>

            @if ($preview->warnings !== [])
                <section class="space-y-3">
                    <h3 class="text-base font-semibold text-foreground">{{ __('ui.feature_import_warnings') }}</h3>
                    <div class="flex flex-col gap-3">
                        @foreach ($preview->warnings as $warning)
                            @php
                                $level = $warning['level'] ?? 'info';
                                $alertClass = $warningLevelClass[$level] ?? 'kt-alert-light kt-alert-info';
                            @endphp
                            <div class="kt-alert {{ $alertClass }}">
                                <div class="kt-alert-content">
                                    {{ $warning['message'] ?? '' }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="rounded-lg border border-border p-4 space-y-2">
                    <h3 class="font-semibold text-foreground">{{ __('ui.feature_import_summary_current') }}</h3>
                    <p><span class="text-muted-foreground">{{ __('ui.title') }}:</span> {{ $preview->existingTitle ?: '—' }}</p>
                    <p><span class="text-muted-foreground">{{ __('ui.scenarios') }}:</span> {{ $preview->scenarioCountExisting() }}</p>
                    @if ($preview->existingScenarioTitles !== [])
                        <ul class="list-disc ps-5 text-muted-foreground">
                            @foreach ($preview->existingScenarioTitles as $title)
                                <li>{{ $title }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                <div class="rounded-lg border border-primary/30 bg-muted/20 p-4 space-y-2">
                    <h3 class="font-semibold text-foreground">{{ __('ui.feature_import_summary_incoming') }}</h3>
                    <p><span class="text-muted-foreground">{{ __('ui.title') }}:</span> {{ $preview->incomingTitle ?: '—' }}</p>
                    <p><span class="text-muted-foreground">{{ __('ui.scenarios') }}:</span> {{ $preview->scenarioCountIncoming() }}</p>
                    @if ($preview->incomingFeatureTags !== [])
                        <p>
                            <span class="text-muted-foreground">{{ __('ui.tags') }}:</span>
                            {{ implode(' ', $preview->incomingFeatureTags) }}
                        </p>
                    @endif
                    @if ($preview->incomingScenarioTitles !== [])
                        <ul class="list-disc ps-5 text-muted-foreground">
                            @foreach ($preview->incomingScenarioTitles as $title)
                                <li>{{ $title }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>

            <section class="space-y-2">
                <h3 class="text-base font-semibold text-foreground">{{ __('ui.feature_import_preserved') }}</h3>
                <p class="text-sm text-muted-foreground">{{ __('ui.feature_import_preserved_help') }}</p>
                <p class="text-sm font-mono">{{ implode(', ', $preview->preservedFields) }}</p>
            </section>

            <form id="feature-import-confirm-form" action="{{ $confirmUrl }}" method="post" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <input type="hidden" name="overwrite_title" value="0">
                <label class="flex items-start gap-3 text-sm">
                    <input
                        type="checkbox"
                        name="overwrite_title"
                        value="1"
                        class="mt-1"
                        @checked(true)
                    >
                    <span>
                        <span class="font-medium text-foreground">{{ __('ui.feature_import_overwrite_title') }}</span>
                        <span class="block text-muted-foreground">{{ __('ui.feature_import_overwrite_title_help') }}</span>
                    </span>
                </label>
            </form>
        </div>

        <div class="kt-card-footer flex flex-wrap justify-end gap-2.5 border-t border-border p-5 lg:p-7.5">
            <x-button type="link" href="{{ $cancelUrl }}" color="outline">Cancel</x-button>
            <x-button type="link" href="{{ $backUrl }}" color="light">{{ __('ui.feature_import_choose_another') }}</x-button>
            <x-button type="submit" form="feature-import-confirm-form" color="primary">{{ __('ui.feature_import_confirm') }}</x-button>
        </div>
    </x-form-card>
@endsection
