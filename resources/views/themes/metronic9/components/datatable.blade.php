@push('styles')
<style>
    /* Metronic-style: fixed layout + explicit rem widths on short columns. */
    table.dataTable {
        table-layout: fixed;
        width: 100%;
    }

    table.dataTable thead th {
        vertical-align: bottom;
    }

    table.dataTable th.dt-nowrap,
    table.dataTable td.dt-nowrap {
        white-space: nowrap;
    }
</style>
@endpush

<div class="kt-card-table">
    <div class="kt-table-wrapper">
        <table class="kt-table kt-table-border table-fixed w-full dataTable no-footer {{ $class }}"
            id="{{ \App\Helpers\Ui::keyset($id, 'id', 'datatable') }}">
            <thead>
                <tr>
                    @foreach ($options['columns'] as $col)
                        @php
                            $colStyle = \App\Helpers\DatatableUi::columnStyle($col);
                            $bodyNowrap = (bool) preg_match('/white-space\s*:\s*nowrap/i', $colStyle);
                        @endphp
                        <th class="sorting{{ $bodyNowrap ? ' dt-nowrap' : '' }}" tabindex="0"
                            data-style="{{ $colStyle }}"
                            @if ($bodyNowrap) data-body-nowrap="1" @endif
                            @if ($colStyle !== '') style="{{ $colStyle }}" @endif>
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
                    $(dtid + ' thead th[data-body-nowrap="1"]').each(function() {
                        api.column($(this).index()).nodes().to$().addClass('dt-nowrap');
                    });
                    if (typeof KTDropdown !== 'undefined' && typeof KTDropdown.createInstances === 'function') {
                        KTDropdown.createInstances();
                    }
                }
            });
        });
    });
</script>
@endpush
