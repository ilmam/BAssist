@extends(ui_layout())

@section('main')
    <x-form-card title="Edit User">
        <x-slot:toolbar>
            <x-button type="link" href="{{ route('admin.users.index') }}" icon="arrow-left" iconOnly="true" color="ghost" size="sm"></x-button>
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
                <div class="kt-form-item">
                    <label class="kt-form-label" for="name">Name</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required class="kt-input" />
                </div>

                <div class="kt-form-item">
                    <label class="kt-form-label" for="email">Email</label>
                    <input id="email" type="email" value="{{ $user->email }}" disabled class="kt-input opacity-70" />
                    <p class="kt-form-description">Email is managed through authentication settings.</p>
                </div>

                <div class="kt-form-item">
                    <label class="kt-form-label" for="role_id">Role</label>
                    @php
                        [$selectAttrs] = ui_form_select_attrs();
                    @endphp
                    <select id="role_id" name="role_id" required @foreach ($selectAttrs as $attr => $attrValue) {{ $attr }}="{{ $attrValue }}" @endforeach>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected((int) old('role_id', $user->role_id) === $role->id)>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="kt-card-footer flex justify-end gap-2.5 border-t border-border p-5 lg:p-7.5">
                <x-button type="link" href="{{ route('admin.users.index') }}" color="outline">Cancel</x-button>
                <x-button type="submit" color="primary">Save</x-button>
            </div>
        </form>
    </x-form-card>
@endsection
