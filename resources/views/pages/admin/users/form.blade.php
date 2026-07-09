@extends(ui_layout())

@section('main')
    <x-form-card title="Edit User">
        <x-slot:toolbar>
            <x-button type="link" href="{{ route('admin.users.index') }}" icon="arrow-left" iconOnly="true" color="light" activeColor="primary"></x-button>
        </x-slot>

        @if ($errors->any())
            <div class="kt-alert kt-alert-destructive mb-5 mx-5 lg:mx-7.5 mt-5">
                <ul class="list-disc ps-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf
            @method('PUT')

            <div class="kt-card-body border-t border-border p-5 lg:p-7.5 space-y-5">
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-foreground" for="name">Name</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required class="kt-input w-full" />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-foreground" for="email">Email</label>
                    <input id="email" type="email" value="{{ $user->email }}" disabled class="kt-input w-full opacity-70" />
                    <p class="text-xs text-secondary-foreground">Email is managed through authentication settings.</p>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-foreground" for="role_id">Role</label>
                    <select id="role_id" name="role_id" required class="kt-input w-full">
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected((int) old('role_id', $user->role_id) === $role->id)>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="kt-card-footer flex justify-end gap-2.5 border-t border-border p-5 lg:p-7.5">
                <x-button type="link" href="{{ route('admin.users.index') }}" color="secondary">Cancel</x-button>
                <x-button type="submit" color="primary">Save</x-button>
            </div>
        </form>
    </x-form-card>
@endsection
