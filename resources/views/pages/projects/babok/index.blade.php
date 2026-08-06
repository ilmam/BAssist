@extends(ui_layout())

@section('main')
    <div class="space-y-5">
        <x-card title="{{ __('ui.babok_documents') }}">
            <x-slot:toolbar>
                <div class="flex flex-wrap items-center gap-2">
                    <x-button
                        type="link"
                        href="{{ route('projects.dashboard', $project) }}"
                        icon="arrow-left"
                        color="light"
                        activeColor="primary"
                    >
                        {{ __('ui.project_dashboard') }}
                    </x-button>
                    <x-button
                        type="link"
                        href="{{ $fullPackUrl }}"
                        icon="{{ entity_icon('export_pack') }}"
                        color="light"
                        activeColor="primary"
                        target="_blank"
                    >
                        {{ __('ui.export_pack') }}
                    </x-button>
                </div>
            </x-slot:toolbar>

            <p class="text-sm text-muted-foreground mb-2">
                <span class="kt-badge kt-badge-outline">{{ $project->code ?: $project->name }}</span>
            </p>
            <p class="text-sm text-muted-foreground mb-0">{{ __('ui.babok_documents_help') }}</p>
            <p class="text-sm text-muted-foreground mt-2 mb-0">{{ __('ui.babok_documents_vs_pack') }}</p>
        </x-card>

        <x-card :title="__('ui.babok_phase_documents')">
            <div class="kt-scrollable-x-auto">
                <table class="kt-table">
                    <thead>
                        <tr>
                            <th>{{ __('ui.babok_document') }}</th>
                            <th>{{ __('ui.babok_reference') }}</th>
                            <th>{{ __('ui.babok_sections') }}</th>
                            <th class="w-24 text-end">{{ __('ui.count') }}</th>
                            <th class="w-28 text-end">{{ __('ui.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($documents as $doc)
                            <tr>
                                <td>
                                    <div class="font-medium text-foreground">{{ $doc['title'] }}</div>
                                    <div class="text-xs text-muted-foreground mt-1">{{ $doc['purpose'] }}</div>
                                </td>
                                <td class="text-sm text-muted-foreground">{{ $doc['babok'] }}</td>
                                <td class="text-sm text-muted-foreground">
                                    <ul class="list-disc ps-4 mb-0 space-y-1">
                                        @foreach ($doc['sections'] as $section)
                                            <li>
                                                {{ $section['heading'] }}
                                                <span class="text-xs">({{ $section['babok'] }})</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td class="text-end">{{ $doc['item_count'] }}</td>
                                <td class="text-end">
                                    <x-button
                                        type="link"
                                        href="{{ $doc['url'] }}"
                                        icon="file-down"
                                        color="primary"
                                        activeColor="primary"
                                        target="_blank"
                                        class="btn-sm"
                                    >
                                        {{ __('ui.open_document') }}
                                    </x-button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
@endsection
