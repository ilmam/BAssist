{{-- Width/wrap rules: M8 keeps a local push. The inline `min-width` below
     (sum of every column's explicit width, see DatatableUi::minTableWidth())
     is a no-op on sparse lists — smaller than the card's natural 100% — and
     only engages on wide lists (Risks, Change Requests, …) to stop the
     width-less identity column being crushed. --}}
@php
    $tableMinWidth = \App\Helpers\DatatableUi::minTableWidth($options['columns']);
@endphp
@push('styles')
<style>
    .table-responsive,
    .table-responsive .dataTables_wrapper,
    table.dataTable {
        width: 100% !important;
        max-width: 100%;
    }
    .table-responsive .dataTables_wrapper { display: block; }
    table.dataTable { table-layout: fixed !important; min-width: 0; }
    table.dataTable thead th {
        height: auto;
        white-space: normal !important;
        overflow-wrap: break-word;
        word-break: normal;
    }
    table.dataTable th,
    table.dataTable td {
        white-space: normal;
        overflow-wrap: break-word;
        word-break: normal;
    }
    table.dataTable tbody td.dt-nowrap {
        white-space: nowrap;
        overflow-wrap: normal;
        word-break: normal;
    }
</style>
@endpush

<div class="table-responsive">
    <table class="table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer {{ $class }}"
        id="{{ \App\Helpers\Ui::keyset($id, 'id', 'datatable') }}"
        style="width: 100%;@if ($tableMinWidth !== '') {{ ' '.$tableMinWidth.';' }}@endif">
        <thead>
            <tr class="text-start text-muted fw-bolder fs-7 text-uppercase gs-0">
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        $(function() {
            var dtid = '#{{ \App\Helpers\Ui::keyset($options, 'id', 'datatable') }}';
            var ajaxUrl = {!! json_encode($options['ajaxUrl'] ?? route($options['dataRoute'], $options['dataRoutParameters'])) !!};
            var rowClassField = {!! json_encode($options['rowClassField'] ?? null) !!};
            var rowClass = {!! json_encode($options['rowClass'] ?? 'is-orphan-row') !!};
            var autoWidth = {!! json_encode((bool) ($options['autoWidth'] ?? false)) !!};
            var table = $(dtid).DataTable({
                processing: true,
                serverSide: true,
                autoWidth: autoWidth,
                ajax: ajaxUrl,
                columns: [
                    @foreach ($options['columns'] as $col)
                        @if (! is_array($col))
                            { data: '{{ $col }}' },
                        @else
                            {
                                orderable: false,
                                searchable: false,
                                data: null,
                                defaultContent: '',
                                render: function(data, type, row, meta) {
                                    @if (array_key_exists('buttons', $col))
                                        var str = {!! json_encode(\App\Helpers\Ui::TableActionCol($col['buttons'])) !!};
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
                                    @elseif (array_key_exists('template', $col))
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
                    $('._dtSearch').on('keyup', function() {
                        table.search($(this).val()).draw();
                    });
                },
                drawCallback: function() {
                    var api = this.api();
                    var $table = $(dtid);
                    $table.css('width', '100%');
                    $table.closest('.dataTables_wrapper').css('width', '100%');
                    $table.closest('.table-responsive').css('width', '100%');
                    $(dtid + ' thead th[data-body-nowrap="1"]').each(function() {
                        api.column($(this).index()).nodes().to$().addClass('dt-nowrap');
                    });
                }
            });
        });
    });
</script>
@endpush
