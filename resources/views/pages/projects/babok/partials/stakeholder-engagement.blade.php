@if ($stakeholders->isEmpty())
    <p class="empty">{{ __('ui.export_none', ['items' => __('ui.stakeholders')]) }}</p>
@else
    <h2 class="section-title">{{ __('ui.stakeholders') }}</h2>
    <table class="matrix">
        <thead>
            <tr>
                <th>{{ __('ui.stakeholder') }}</th>
                <th>{{ __('ui.type') }}</th>
                <th>{{ __('ui.responsibility') }}</th>
                <th>{{ __('ui.influence') }}</th>
                <th>{{ __('ui.interest') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($stakeholders as $stakeholder)
                <tr>
                    <td>{{ $stakeholder->name ?: '—' }}</td>
                    <td>{{ $stakeholder->type ?: '—' }}</td>
                    <td>{{ $stakeholder->notes ?: '—' }}</td>
                    <td>{{ $stakeholder->influence ?: '—' }}</td>
                    <td>{{ $stakeholder->interest ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
