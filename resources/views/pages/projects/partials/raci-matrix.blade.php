@php
    $raciItems = ($raci_items ?? collect())->filter(fn ($item) => $item->assignments->isNotEmpty());
    $raciStakeholders = ($stakeholders ?? collect())
        ->filter(fn ($stakeholder) => $stakeholder->raciAssignments->isNotEmpty())
        ->values();
@endphp

@if ($raciItems->isNotEmpty())
    <h3 class="item-title">{{ __('ui.raci_matrix') }}</h3>
    <table class="matrix">
        <thead>
            <tr>
                <th>{{ __('ui.raci_item') }}</th>
                @foreach ($raciStakeholders as $stakeholder)
                    <th>{{ $stakeholder->name }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($raciItems as $item)
                <tr>
                    <td>{{ $item->title }}</td>
                    @foreach ($raciStakeholders as $stakeholder)
                        <td>{{ $item->assignments->firstWhere('stakeholder_id', $stakeholder->id)?->role ?? '' }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="prose">R = Responsible · A = Accountable · C = Consulted · I = Informed</p>
@endif
