<table class="table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer {{ $class }}"
    id="{{ \App\Helpers\Ui::keyset($id, 'id', 'datatable') }}">
    <thead>
        <tr class="text-start text-muted fw-bolder fs-7 text-uppercase gs-0">
            @foreach ($options['columns'] as $col)
                <th class="sorting" tabindex="0" data-style="{{ Ui::keyset($col, 'style') }}">
                    @if (! is_array($col))
                        {{ $col }}
                    @else
                        {{ $col['title'] ?? $col['name'] ?? $col['data'] ?? '' }}
                    @endif
                </th>
            @endforeach
        </tr>
    </thead>
</table>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        $(function() {
            var dtid = '#{{ \App\Helpers\Ui::keyset($options, 'id', 'datatable') }}';
            var table = $(dtid).DataTable({
                processing: true,
                serverSide: true,
                ajax: '{!! route($options['dataRoute'], $options['dataRoutParameters']) !!}',
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
                                        var str = '{!! $col['template'] !!}';
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
                                            str = str.split('{{ '{'.$key.'}' }}').join(String(row.{{ $key }}));
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
                initComplete: function() {
                    $('.dataTable th[data-style!=""]').each(function() {
                        $(this).attr('style', $(this).data('style'));
                    });

                    $('._dtSearch').on('keyup', function() {
                        table.search($(this).val()).draw();
                    });
                }
            });
        });
    });
</script>
@endpush
