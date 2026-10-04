@php
    $entities = \App\Support\CrudEntityRegistry::all();
@endphp

<div class="border-t border-border pt-5">
    <h4 class="text-base font-semibold text-foreground mb-2">Entity permissions</h4>

    @if ($readOnly ?? false)
        <x-alert>
            The super admin role has full access to all entities. Permissions cannot be restricted.
        </x-alert>
    @else
        <p class="text-sm text-secondary-foreground mb-4">
            Choose which CRUD entities this role can access and what actions are allowed.
        </p>
    @endif

    <div class="kt-card-table">
        <div class="kt-table-wrapper overflow-x-auto">
            <table class="kt-table kt-table-border w-full">
                <thead>
                    <tr>
                        <th>Entity</th>
                        <th class="text-center">View</th>
                        <th class="text-center">Create</th>
                        <th class="text-center">Update</th>
                        <th class="text-center">Delete</th>
                        <th class="text-center" title="Sign off requirements (BABOK 5.5). Only for items that go through review.">Approve</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entities as $model => $options)
                        @php
                            $flags = $permissionMatrix[$model] ?? ['view' => false, 'create' => false, 'update' => false, 'delete' => false, 'approve' => false];
                            $label = $options['nav_label'] ?? \Illuminate\Support\Str::plural($model);
                        @endphp
                        <tr>
                            <td class="font-medium">{{ $label }}</td>
                            @foreach (['view', 'create', 'update', 'delete'] as $ability)
                                <td class="text-center">
                                    <input
                                        type="checkbox"
                                        name="permissions[{{ $model }}][{{ $ability }}]"
                                        value="1"
                                        class="kt-checkbox"
                                        @checked(old("permissions.$model.$ability", $readOnly ?? false ? true : $flags[$ability]))
                                        @disabled($readOnly ?? false)
                                    />
                                </td>
                            @endforeach
                            <td class="text-center">
                                @if (in_array($model, \App\Services\ApprovalService::APPROVABLE, true))
                                    <input
                                        type="checkbox"
                                        name="permissions[{{ $model }}][approve]"
                                        value="1"
                                        class="kt-checkbox"
                                        aria-label="Approve {{ $label }}"
                                        @checked(old("permissions.$model.approve", $readOnly ?? false ? true : ($flags['approve'] ?? false)))
                                        @disabled($readOnly ?? false)
                                    />
                                @else
                                    <span class="text-muted-foreground" aria-hidden="true">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-secondary-foreground">No routable entities are registered yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
