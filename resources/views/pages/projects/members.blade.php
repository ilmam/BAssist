@extends(ui_layout())

@section('main')
    <x-card title="{{ __('ui.members_title', ['project' => $project->name]) }}">
        <x-slot:toolbar>
            <x-button type="link" href="{{ route('projects.dashboard', $project) }}" icon="arrow-left" iconOnly="true" color="ghost" size="sm"></x-button>
        </x-slot>

        @if (session('status'))
            <div class="kt-alert kt-alert-success mb-5">{{ session('status') }}</div>
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

        @php
            [$selectAttrs] = ui_form_select_attrs();
        @endphp

        <p class="text-sm text-muted-foreground mb-5">{{ __('ui.members_intro') }}</p>

        <div class="kt-card-table">
            <div class="kt-table-wrapper">
                <table class="kt-table kt-table-border w-full">
                    <thead>
                        <tr>
                            <th>{{ __('ui.name') }}</th>
                            <th>{{ __('ui.email') }}</th>
                            <th>{{ __('ui.members_access') }}</th>
                            <th class="text-end" style="width: 120px">{{ __('ui.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($members as $member)
                            <tr>
                                <td class="font-medium">
                                    {{ $member->user?->name }}
                                    @if ($member->user?->is_guest)
                                        <span class="kt-badge kt-badge-outline ms-1">{{ __('ui.members_guest') }}</span>
                                    @endif
                                </td>
                                <td>{{ $member->user?->email }}</td>
                                <td>
                                    <form method="POST" action="{{ route('projects.members.update', [$project, $member]) }}" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="access" @foreach ($selectAttrs as $attr => $attrValue) {{ $attr }}="{{ $attrValue }}" @endforeach>
                                            @foreach ($levels as $level)
                                                <option value="{{ $level->value }}" @selected($member->access === $level)>{{ $level->label() }}</option>
                                            @endforeach
                                        </select>
                                        <x-button type="submit" color="outline" size="sm">{{ __('ui.save') }}</x-button>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('projects.members.destroy', [$project, $member]) }}" class="flex justify-end">
                                        @csrf
                                        @method('DELETE')
                                        <x-button type="submit" color="outline" size="sm">{{ __('ui.members_remove') }}</x-button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-secondary-foreground">{{ __('ui.members_none') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($invitations->isNotEmpty())
            <h3 class="text-sm font-medium text-foreground mt-8 mb-3">{{ __('ui.members_pending') }}</h3>
            <div class="kt-card-table">
                <div class="kt-table-wrapper">
                    <table class="kt-table kt-table-border w-full">
                        <thead>
                            <tr>
                                <th>{{ __('ui.email') }}</th>
                                <th>{{ __('ui.members_access') }}</th>
                                <th>{{ __('ui.members_expires') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invitations as $invitation)
                                <tr>
                                    <td class="font-medium">{{ $invitation->email }}</td>
                                    <td>{{ $invitation->access->label() }}</td>
                                    <td>{{ $invitation->expires_at->toFormattedDateString() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </x-card>
@endsection
