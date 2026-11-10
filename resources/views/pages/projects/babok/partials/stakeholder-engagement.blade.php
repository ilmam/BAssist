@if ($stakeholders->isEmpty())
    <p class="empty">{{ __('ui.export_none', ['items' => __('ui.stakeholders')]) }}</p>
@else
    <h2 class="section-title">{{ __('ui.stakeholders') }}</h2>
    <table class="matrix">
        <thead>
            <tr>
                <th>{{ __('ui.stakeholder') }}</th>
                <th>{{ __('ui.type') }}</th>
                <th>{{ __('ui.influence') }}</th>
                <th>{{ __('ui.interest') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($stakeholders as $stakeholder)
                <tr>
                    <td>{{ $stakeholder->name ?: '—' }}</td>
                    <td>{{ \App\Support\StakeholderType::label($stakeholder->type) }}</td>
                    <td>{{ \App\Support\StakeholderLevel::label($stakeholder->influence) }}</td>
                    <td>{{ \App\Support\StakeholderLevel::label($stakeholder->interest) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
