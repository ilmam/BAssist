@if (! empty($filterChips))
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <span class="text-sm text-muted-foreground">{{ __('ui.filtered_by') }}:</span>
        @foreach ($filterChips as $chip)
            <a href="{{ $chip['clear_url'] }}"
               class="kt-badge kt-badge-outline kt-badge-warning gap-1"
               title="{{ match ($chip['param'] ?? '') {
                   'workspace_id' => __('ui.clear_workspace'),
                   'project_id' => __('ui.clear_project'),
                   default => __('ui.clear_filter'),
               } }}">
                <span>{{ $chip['label'] }}: {{ $chip['value'] }}</span>
                <i class="ki-filled ki-cross text-xs"></i>
            </a>
        @endforeach
        <a href="{{ $clearAllUrl ?? model_route($model, 'index') }}" class="text-sm text-primary underline-offset-2 hover:underline">
            {{ __('ui.clear_all_filters') }}
        </a>
    </div>
@endif
