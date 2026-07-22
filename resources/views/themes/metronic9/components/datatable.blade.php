@push('styles')
<style>
    table.dataTable thead th {
        white-space: normal !important;
        overflow-wrap: anywhere;
        word-break: break-word;
        vertical-align: bottom;
    }
</style>
@endpush

<div class="kt-card-table">
    <div class="kt-table-wrapper">
        <table class="kt-table kt-table-border w-full dataTable no-footer {{ $class }}"
            id="{{ \App\Helpers\Ui::keyset($id, 'id', 'datatable') }}">
            <thead>
                <tr>
                    @foreach ($options['columns'] as $col)
                        @php
                            $colStyle = (string) Ui::keyset($col, 'style');
                            $bodyNowrap = (bool) preg_match('/white-space\s*:\s*nowrap/i', $colStyle);
                        @endphp
                        <th class="sorting" tabindex="0" data-style="{{ $colStyle }}" @if ($bodyNowrap) data-body-nowrap="1" @endif>
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
            var table = $(dtid).DataTable({
                processing: true,
                serverSide: true,
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
                    $(dtid + ' thead th[data-style!=""]').each(function() {
                        var $th = $(this);
                        var style = String($th.data('style') || '');
                        var headerStyle = style.replace(/white-space\s*:\s*nowrap\s*;?/gi, '').trim();
                        if (headerStyle) {
                            $th.attr('style', headerStyle);
                        }
                    });

                    $('._dtSearch').on('keyup', function() {
                        table.search($(this).val()).draw();
                    });
                },
                drawCallback: function() {
                    var api = this.api();
                    $(dtid + ' thead th[data-body-nowrap="1"]').each(function() {
                        api.column($(this).index()).nodes().to$().css('white-space', 'nowrap');
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
