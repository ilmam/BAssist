{{--
    The element rows as a list: a filter bar and a table. Read-only on the details page;
    editable inside the screen form (pages/screens/partials/elements-designer).

    In: $elements (rows), $editable (bool), $requirements (id => "FR-1 — title").
--}}
@php
    use App\Support\ScreenElementKind;

    $editable = (bool) ($editable ?? false);
    $rows = is_array($elements ?? null) ? array_values($elements) : [];
    $requirements = is_array($requirements ?? null) ? $requirements : [];
    $kinds = ScreenElementKind::selectOptions();

    // Nesting depth per row, for the indent in the read-only list (the editor does it in JS).
    $parentOf = [];
    foreach ($rows as $r) {
        $parentOf[(string) ($r['key'] ?? '')] = (string) ($r['parent_key'] ?? '');
    }
    $depthOf = function (string $key) use ($parentOf): int {
        $depth = 0;
        for ($p = $parentOf[$key] ?? ''; $p !== '' && $depth < 12; $p = $parentOf[$p] ?? '') {
            $depth++;
        }

        return $depth;
    };
@endphp

<div class="space-y-3" data-element-list>
    {{-- Filter bar: narrows the table only; hidden rows are still saved. No name= so none of it is submitted. --}}
    <div class="flex flex-wrap items-end gap-3">
        <div class="grow max-w-xs">
            <label class="kt-form-label mb-1.5" for="element-filter-text-{{ $editable ? 'edit' : 'view' }}">{{ __('ui.search') }}</label>
            <input type="search" id="element-filter-text-{{ $editable ? 'edit' : 'view' }}" class="kt-input" data-filter-text placeholder="{{ __('ui.screen_filter_label') }}" autocomplete="off">
        </div>
        <div class="max-w-xs">
            <label class="kt-form-label mb-1.5" for="element-filter-kind-{{ $editable ? 'edit' : 'view' }}">{{ __('ui.screen_element_kind') }}</label>
            <select id="element-filter-kind-{{ $editable ? 'edit' : 'view' }}" class="kt-select" data-filter-kind>
                <option value="">{{ __('ui.all') }}</option>
                @foreach ($kinds as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="button" class="kt-btn kt-btn-outline" data-filter-clear>{{ __('ui.screen_filter_clear') }}</button>
        <span class="text-sm text-muted-foreground ms-auto" data-filter-count></span>
    </div>

    <div class="overflow-x-auto border border-border rounded-lg" data-table-density="compact">
        <table class="kt-table kt-table--compact table-auto w-full" data-elements-table>
            <thead>
                <tr>
                    <th class="w-12">#</th>
                    <th class="w-20">{{ __('ui.screen_element_row') }}</th>
                    <th class="min-w-32">{{ __('ui.screen_element_kind') }}</th>
                    @if ($editable)
                        <th class="min-w-40">{{ __('ui.screen_element_inside') }}</th>
                    @endif
                    <th class="min-w-56">{{ __('ui.screen_element_label') }}</th>
                    <th class="min-w-48">{{ __('ui.screen_element_requirement') }}</th>
                    @if ($editable)
                        <th class="w-32">{{ __('ui.actions') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody data-elements-body>
                @foreach ($rows as $index => $row)
                    @include('pages.screens.partials.element-row', [
                        'index' => $index,
                        'depth' => $depthOf((string) ($row['key'] ?? '')),
                        'row' => $row,
                        'editable' => $editable,
                        'kinds' => $kinds,
                        'requirements' => $requirements,
                    ])
                @endforeach
            </tbody>
        </table>
    </div>

    @if (! $editable && $rows === [])
        <p class="text-sm text-muted-foreground">{{ __('ui.screen_no_elements_yet') }}</p>
    @endif
</div>
