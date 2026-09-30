{{-- Width/wrap rules live in public/.../bassist.css (fixed layout + 100% width).
     The inline `min-width` below (sum of every column's explicit width, see
     DatatableUi::minTableWidth()) is a no-op on sparse lists — smaller than
     the card's natural 100% — and only engages on wide lists (Risks, Change
     Requests, …) to stop the width-less identity column being crushed. --}}

@php
    $tableMinWidth = \App\Helpers\DatatableUi::minTableWidth($options['columns']);
    $tableDomId = (string) ($options['id'] ?? (is_string($id) && $id !== '' ? $id : 'datatable'));
    // Status / priority / risk-level columns render as tone badges (see ui_status_tones()).
    $badgeFieldPattern = '/(^|\.)(status|priority|impact|likelihood|response|severity)(\.(name|code|label))?$/';
    $emptyStateHtml = $options['emptyStateHtml'] ?? \Illuminate\Support\Facades\Blade::render(
        '<x-empty-state icon="questionnaire-tablet" :title="$t" :hint="$h" compact />',
        ['t' => __('ui.list_empty_title'), 'h' => __('ui.list_empty_hint')],
    );
    // #6 quick edit + bulk: enabled by the list page with the model's status / priority options.
    $quickEdit = $options['quickEdit'] ?? [];
    $quickModel = (string) ($options['model'] ?? '');
    $quickEnabled = $quickEdit !== [] && $quickModel !== '';
    $quickFieldFor = [
        'status.name' => 'status_id',
        'status.code' => 'status_id',
        'priority.name' => 'priority_id',
        'priority.code' => 'priority_id',
    ];
    $noMatchHtml = \Illuminate\Support\Facades\Blade::render(
        '<x-empty-state icon="magnifier" :title="$t" :hint="$h" compact />',
        ['t' => __('ui.list_no_match_title'), 'h' => __('ui.list_no_match_hint')],
    );
@endphp

<div class="kt-card-table"
    @if ($quickEnabled)
        data-quick-table="#{{ $tableDomId }}"
        data-quick-model="{{ $quickModel }}"
        data-quick-update-url="{{ route('quick.update', ['model' => $quickModel, 'id' => '__ID__']) }}"
        data-quick-bulk-url="{{ route('quick.bulk', ['model' => $quickModel]) }}"
        data-csrf="{{ csrf_token() }}"
    @endif
>
    @if ($quickEnabled)
        <script type="application/json" data-quick-options>@json($quickEdit)</script>
        <div class="ba-bulkbar" data-bulkbar hidden role="region" aria-label="{{ __('ui.bulk_actions') }}">
            <span class="ba-bulkbar__count" data-bulk-count aria-live="polite"></span>
            @foreach ($quickEdit as $field => $choices)
                <label class="ba-bulkbar__field">
                    <span>{{ __('ui.bulk_set_'.$field) }}</span>
                    <select data-bulk-field="{{ $field }}">
                        <option value="">{{ __('ui.bulk_choose') }}</option>
                        @foreach ($choices as $choice)
                            <option value="{{ $choice['id'] }}">{{ $choice['name'] }}</option>
                        @endforeach
                    </select>
                </label>
            @endforeach
            <button type="button" class="{{ ui_btn_classes('primary', 'sm') }}" data-bulk-apply>{{ __('ui.bulk_apply') }}</button>
            <button type="button" class="{{ ui_btn_classes('ghost', 'sm') }}" data-bulk-clear>{{ __('ui.bulk_clear') }}</button>
        </div>
    @endif
    <div class="kt-table-wrapper">
        <table class="kt-table kt-table-border w-full dataTable no-footer {{ $class }}"
            id="{{ $tableDomId }}"
            style="width: 100%;@if ($tableMinWidth !== '') {{ ' '.$tableMinWidth.';' }}@endif">
            <thead>
                <tr>
                    @if ($quickEnabled)
                        <th class="ba-select-col" style="width: 40px;">
                            <input type="checkbox" class="ba-check" data-bulk-all aria-label="{{ __('ui.bulk_select_all') }}">
                        </th>
                    @endif
                    @foreach ($options['columns'] as $col)
                        @php
                            $colStyle = \App\Helpers\DatatableUi::columnStyle($col, $loop->index);
                            $headerStyle = \App\Helpers\DatatableUi::headerStyle($colStyle);
                            $bodyNowrap = (bool) preg_match('/white-space\s*:\s*nowrap/i', $colStyle);
                        @endphp
                        <th class="sorting" tabindex="0"
                            data-style="{{ $colStyle }}"
                            @if ($bodyNowrap) data-body-nowrap="1" @endif
                            @if ($headerStyle !== '') style="{{ $headerStyle }}" @endif>
                            @if (! is_array($col))
                                {{ Ui::fieldLabel((string) $col) }}
                            @else
                                {{ $col['title'] ?? Ui::fieldLabel((string) ($col['name'] ?? $col['data'] ?? '')) }}
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
        </table>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        $(function() {
            var dtid = '#{{ $tableDomId }}';
            var ajaxUrl = {!! json_encode($options['ajaxUrl'] ?? route($options['dataRoute'], $options['dataRoutParameters'])) !!};
            var rowClassField = {!! json_encode($options['rowClassField'] ?? null) !!};
            var rowClass = {!! json_encode($options['rowClass'] ?? 'is-orphan-row') !!};
            var autoWidth = {!! json_encode((bool) ($options['autoWidth'] ?? false)) !!};
            var pageLength = {!! json_encode((int) ($options['pageLength'] ?? 10)) !!};
            var codeModalUrl = {!! json_encode($options['codeModalUrl'] ?? null) !!};
            var codePageUrl = {!! json_encode($options['codePageUrl'] ?? null) !!};
            var baTones = {!! json_encode(ui_status_tones()) !!};
            var baDecode = function(html) { return $('<textarea>').html(String(html)).text(); };
            var baStatusRender = function(data, type) {
                if (data === null || data === undefined || data === '') {
                    return type === 'display' ? '<span class="text-muted-foreground">—</span>' : '';
                }
                if (type !== 'display') {
                    return data;
                }
                var raw = baDecode(data).trim();
                var key = raw.toLowerCase().replace(/['’]/g, '').replace(/[\s\-]+/g, '_');
                var tone = baTones[key] || 'neutral';
                var label = /^[a-z_]+$/.test(raw)
                    ? raw.replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); })
                    : raw;
                return '<span class="ba-badge ba-badge--' + tone + '"><span class="ba-badge__dot" aria-hidden="true"></span>'
                    + $('<div>').text(label).html() + '</span>';
            };
            var table = $(dtid).DataTable({
                processing: true,
                language: {
                    emptyTable: {!! json_encode($emptyStateHtml) !!},
                    zeroRecords: {!! json_encode($noMatchHtml) !!}
                },
                serverSide: true,
                autoWidth: autoWidth,
                pageLength: pageLength,
                ajax: ajaxUrl,
                columns: [
                    @if ($quickEnabled)
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'ba-select-col',
                            render: function(data, type, row) {
                                if (type !== 'display' || !row.id) { return ''; }
                                return '<input type="checkbox" class="ba-check" data-bulk-row value="' + Number(row.id) + '" aria-label="{{ __('ui.bulk_select_row') }}">';
                            }
                        },
                    @endif
                    @foreach ($options['columns'] as $col)
                        @php $dataField = \App\Helpers\DatatableUi::columnDataField($col); @endphp
                        @if ($dataField !== null && $dataField === 'code')
                            {
                                data: 'code',
                                render: function(data, type, row) {
                                    if (type !== 'display') {
                                        return data;
                                    }
                                    if (data === null || data === undefined || data === '') {
                                        return '';
                                    }
                                    var text = $('<div>').text(baDecode(data)).html();
                                    if (!codeModalUrl || !codePageUrl || !row.id) {
                                        return '<span class="ba-code-chip">' + text + '</span>';
                                    }
                                    var modalUrl = String(codeModalUrl).split('{id}').join(String(row.id));
                                    var pageUrl = String(codePageUrl).split('{id}').join(String(row.id));
                                    return '<a href="' + pageUrl + '" class="ba-code-chip js-open-modal" data-modal-url="' + modalUrl + '">' + text + '</a>';
                                }
                            },
                        @elseif ($dataField !== null && $quickEnabled && isset($quickFieldFor[$dataField], $quickEdit[$quickFieldFor[$dataField]]))
                            {
                                data: '{{ $dataField }}',
                                defaultContent: '',
                                render: function(data, type, row) {
                                    var html = baStatusRender(data, type);
                                    if (type !== 'display' || !row.id) { return html; }
                                    var field = '{{ $quickFieldFor[$dataField] }}';
                                    if (!data) { html = '<span class="ba-badge ba-badge--neutral">{{ __('ui.quick_set') }}</span>'; }
                                    return '<button type="button" class="ba-quick-badge" data-quick-field="' + field + '" data-quick-id="' + Number(row.id) + '" data-quick-current="' + (row[field] == null ? '' : Number(row[field])) + '" aria-haspopup="listbox" title="{{ __('ui.quick_change') }}">' + html + '<i class="ki-filled ki-down" aria-hidden="true"></i></button>';
                                }
                            },
                        @elseif ($dataField !== null && preg_match($badgeFieldPattern, $dataField))
                            { data: '{{ $dataField }}', defaultContent: '', render: baStatusRender },
                        @elseif ($dataField !== null)
                            { data: '{{ $dataField }}' },
                        @else
                            {
                                orderable: false,
                                searchable: false,
                                data: null,
                                defaultContent: '',
                                render: function(data, type, row, meta) {
                                    @if (is_array($col) && array_key_exists('buttons', $col))
                                        var str = {!! json_encode(\App\Helpers\Ui::TableActionCol($col['buttons'], (bool) ($col['collapsed'] ?? false))) !!};
                                        @foreach (array_unique($options['keys'] ?? ['id']) as $key)
                                            str = str.split('{{ '{'.$key.'}' }}').join(String(row.{{ $key }}));
                                        @endforeach
                                        var isSystem = row.is_system === true || row.is_system === 1 || row.is_system === '1' || row.is_system === 'true';
                                        if (isSystem) {
                                            var wrap = document.createElement('div');
                                            wrap.innerHTML = str;
                                            wrap.querySelectorAll('[data-action="delete"]').forEach(function(el) { el.remove(); });
                                            str = wrap.innerHTML;
                                        }
                                        return str;
                                    @elseif (is_array($col) && array_key_exists('template', $col))
                                        var str = {!! json_encode($col['template']) !!};
                                        @if (array_key_exists('field', $col))
                                            @php $fields = [$col['field']]; @endphp
                                        @elseif (array_key_exists('fields', $col) && ! is_array($col['fields']))
                                            @php $fields = [$col['fields']]; @endphp
                                        @elseif (array_key_exists('fields', $col) && is_array($col['fields']))
                                            @php $fields = $col['fields']; @endphp
                                        @else
                                            @php $fields = $options['keys']; @endphp
                                        @endif
                                        @foreach ($fields as $key)
                                            str = str.split('{{ '{'.$key.'}' }}').join(String(row['{{ $key }}'] ?? ''));
                                        @endforeach
                                        return str;
                                    @else
                                        return '';
                                    @endif
                                }
                            },
                        @endif
                    @endforeach
                ],
                createdRow: function(row, data) {
                    if (!rowClassField) {
                        return;
                    }
                    var flag = data[rowClassField];
                    if (flag === true || flag === 1 || flag === '1' || flag === 'true') {
                        $(row).addClass(rowClass);
                    }
                },
                initComplete: function() {
                    document.dispatchEvent(new CustomEvent('ba:datatable-ready', { detail: { selector: dtid, table: table } }));
                    $('._dtSearch').on('keyup', function() {
                        table.search($(this).val()).draw();
                    });
                },
                drawCallback: function() {
                    var api = this.api();
                    var $table = $(dtid);
                    $table.css('width', '100%');
                    $table.closest('.dataTables_wrapper').css('width', '100%');
                    $table.closest('.kt-table-wrapper, .table-responsive').css('width', '100%');
                    $(dtid + ' thead th[data-body-nowrap="1"]').each(function() {
                        api.column($(this).index()).nodes().to$().addClass('dt-nowrap');
                    });
                    if (typeof KTDropdown !== 'undefined' && typeof KTDropdown.createInstances === 'function') {
                        KTDropdown.createInstances();
                    }
                    if (typeof KTMenu !== 'undefined' && typeof KTMenu.createInstances === 'function') {
                        KTMenu.createInstances();
                    }
                }
            });
        });
    });
</script>
@endpush
