@props([
    'activeCount' => 0,
    'clearUrl' => null,
])

<details {{ $attributes->class(['list-filter-panel']) }}>
    <summary class="list-filter-panel__summary">
        <span class="list-filter-panel__title">
            <i class="ki-filled ki-filter-search text-base"></i>
            <span>{{ __('ui.filters') }}</span>
            @if ((int) $activeCount > 0)
                <span class="kt-badge kt-badge-sm kt-badge-primary">{{ (int) $activeCount }}</span>
            @endif
        </span>
        <i class="ki-filled ki-down list-filter-panel__chevron text-sm text-muted-foreground"></i>
    </summary>

    <div class="list-filter-panel__body">
        {{ $slot }}

        @if (filled($clearUrl) && (int) $activeCount > 0)
            <div class="list-filter-panel__clear">
                <a href="{{ $clearUrl }}" class="text-sm text-primary underline-offset-2 hover:underline">
                    {{ __('ui.clear_all_filters') }}
                </a>
            </div>
        @endif
    </div>
</details>

@pushOnce('scripts')
<script>
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || !form.matches || !form.matches('[data-list-filter-form]')) {
            return;
        }

        function syncClearFlag(selectName, flagName) {
            var select = form.querySelector('[name="' + selectName + '"]');
            if (!select) {
                return;
            }

            var existing = form.querySelector('input[name="' + flagName + '"]');
            if (!select.value) {
                if (!existing) {
                    existing = document.createElement('input');
                    existing.type = 'hidden';
                    existing.name = flagName;
                    form.appendChild(existing);
                }
                existing.value = '1';
            } else if (existing) {
                existing.remove();
            }
        }

        syncClearFlag('project_id', 'clear_project');
        syncClearFlag('workspace_id', 'clear_workspace');
    }, true);
</script>
@endPushOnce
