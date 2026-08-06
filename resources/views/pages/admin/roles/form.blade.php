@php
    $isEdit = $role->exists;
    $title = ($isEdit ? 'Edit' : 'Create').' Role';
@endphp

@extends(ui_layout())

@section('main')
    <x-form-card :title="$title">
        <x-slot:toolbar>
            <x-button type="link" href="{{ route('admin.roles.index') }}" icon="arrow-left" iconOnly="true" color="ghost" size="sm"></x-button>
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

        <form method="POST" action="{{ $isEdit ? route('admin.roles.update', $role) : route('admin.roles.store') }}">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="kt-card-body border-t border-border p-5 lg:p-7.5 space-y-5">
                <div class="kt-form-item">
                    <label class="kt-form-label" for="name">Name</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $role->name) }}" required class="kt-input" />
                </div>

                <div class="kt-form-item">
                    <label class="kt-form-label" for="slug">Slug</label>
                    @if ($role->isSuperAdmin())
                        <input id="slug" type="text" value="{{ $role->slug }}" disabled class="kt-input opacity-70" />
                        <p class="kt-form-description">The super admin slug is fixed.</p>
                    @else
                        <input id="slug" type="text" name="slug" value="{{ old('slug', $role->slug) }}" required class="kt-input" />
                        <p class="kt-form-description">Used internally. Lowercase letters, numbers, and dashes only.</p>
                    @endif
                </div>

                @include('pages.admin.roles.partials.permission-matrix', [
                    'permissionMatrix' => $permissionMatrix,
                    'readOnly' => $role->isSuperAdmin(),
                ])
            </div>

            <div class="kt-card-footer flex justify-end gap-2.5 border-t border-border p-5 lg:p-7.5">
                <x-button type="link" href="{{ route('admin.roles.index') }}" color="outline">Cancel</x-button>
                <x-button type="submit" color="primary">Save</x-button>
            </div>
        </form>
    </x-form-card>
@endsection
