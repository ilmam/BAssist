@extends(ui_layout())

@section('main')
    <x-card title="Roles">
        <x-slot:toolbar>
            <x-button type="link" href="{{ route('admin.roles.create') }}" icon="plus" iconOnly="true" color="primary" activeColor="primary"></x-button>
        </x-slot>

        @if ($errors->any())
            <div class="kt-alert kt-alert-destructive mb-5">
                <ul class="list-disc ps-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="kt-card-table">
            <div class="kt-table-wrapper">
                <table class="kt-table kt-table-border w-full">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Users</th>
                            <th class="text-end" style="width: 120px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $role)
                            <tr>
                                <td class="font-medium">{{ $role->name }}</td>
                                <td><code>{{ $role->slug }}</code></td>
                                <td>{{ $role->users_count }}</td>
                                <td>
                                    <div class="flex items-center justify-end gap-1">
                                        <x-button type="link" href="{{ route('admin.roles.edit', $role) }}" icon="pencil" iconOnly="true" color="primary" activeColor="primary"></x-button>
                                        @unless ($role->isSuperAdmin())
                                            <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" onsubmit="return confirm('Delete this role?');">
                                                @csrf
                                                @method('DELETE')
                                                <x-button type="submit" icon="trash" iconOnly="true" color="danger" activeColor="warning"></x-button>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-secondary-foreground">No roles found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-card>
@endsection
