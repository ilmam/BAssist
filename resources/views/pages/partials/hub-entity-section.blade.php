{{-- Hub section card: description + standard entity DataTable (modal show/edit/delete). --}}
@php
    use Illuminate\Support\Str;

    $model = $section['model'];
    $tableId = $section['table_id'] ?? ('hub-'.Str::snake($model));
    $emptyMessage = $emptyMessage ?? __('ui.solution_requirements_empty');
    $columns = $section['columns'] ?? ['code', 'title', 'project.name', 'status.name'];

    $options = [
        'id' => $tableId,
        'columns' => $columns,
        'keys' => ['id'],
        'model' => $model,
        'dataRoute' => 'api.'.Str::snake($model).'.index',
        'dataRoutParameters' => ['modelName' => $model],
        'ajaxUrl' => $section['ajax_url'],
        'pageLength' => $section['page_length'] ?? 10,
    ];
@endphp

<x-card :title="$section['label']">
    <x-slot:titleAside>
        @if (! empty($section['help_topic']))
            <x-help-trigger :topic="$section['help_topic']" />
        @else
            <x-help-trigger :model="$model" />
        @endif
    </x-slot:titleAside>

    @if (empty($section['placeholder']))
        <x-slot:toolbar>
            <div class="flex flex-wrap items-center gap-2">
                @if (array_key_exists('count', $section))
                    <span class="kt-badge kt-badge-outline">{{ $section['count'] }}</span>
                @endif
                <x-button type="link" href="{{ $section['index_url'] }}" color="ghost" size="sm" activeColor="primary">
                    {{ __('ui.view_all') }}
                </x-button>
                @if (! empty($section['can_create']) && ! empty($section['create_modal_url']))
                    <x-button
                        type="link"
                        href="{{ $section['create_modal_url'] }}"
                        icon="plus"
                        iconOnly="true"
                        color="primary"
                        activeColor="primary"
                        class="js-open-modal"
                        data-modal-url="{{ $section['create_modal_url'] }}"
                    ></x-button>
                @endif
            </div>
        </x-slot:toolbar>
    @endif

    <div class="flex items-start gap-3 mb-4">
        <i class="ki-filled ki-{{ $section['icon'] }} text-2xl text-primary shrink-0"></i>
        <p class="text-sm text-muted-foreground">{{ $section['description'] }}</p>
    </div>

    @if (! empty($section['placeholder']))
        <p class="text-sm text-secondary-foreground">{{ $section['placeholder_message'] ?? $emptyMessage }}</p>
    @else
        <x-datatable
            :id="$tableId"
            :options="$options"
            :defaultButtons="true"
        />
    @endif
</x-card>
