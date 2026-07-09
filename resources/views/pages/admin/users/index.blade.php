@extends(ui_layout())

@section('main')
    <x-card title="Users">
        @if (session('status'))
            <x-alert>{{ session('status') }}</x-alert>
        @endif

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
                            <th>Email</th>
                            <th>Role</th>
                            <th class="text-end" style="width: 80px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td class="font-medium">{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->role?->name ?? '—' }}</td>
                                <td>
                                    <div class="flex items-center justify-end gap-1">
                                        <x-button type="link" href="{{ route('admin.users.edit', $user) }}" icon="pencil" iconOnly="true" color="primary" activeColor="primary"></x-button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-secondary-foreground">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-card>
@endsection
