{{-- Projects that came from a brief, with the part of the ask each takes. No rows is a valid outcome. --}}
@php
    $brief = \App\Models\Brief::query()->with('projects')->find($briefId);
    $canEdit = $brief !== null && entity_can('Brief', 'update');
    $linkedIds = $brief?->projects->pluck('id')->all() ?? [];
    $candidates = $canEdit
        ? \App\Models\Project::query()->where('workspace_id', $brief->workspace_id)->whereNotIn('id', $linkedIds)->orderBy('name')->get(['id', 'name'])
        : collect();
    [$selectAttrs] = ui_form_select_attrs();
@endphp

@if ($brief !== null)
    <x-card title="{{ __('ui.brief_projects') }}">
        @if ($errors->any())
            <div class="kt-alert kt-alert-destructive mb-5">
                <ul class="list-disc ps-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($brief->projects->isEmpty())
            <p class="text-sm text-muted-foreground mb-4">{{ __('ui.brief_projects_empty') }}</p>
        @else
            <div class="space-y-3 mb-6">
                @foreach ($brief->projects as $project)
                    <div class="flex flex-wrap items-start gap-3 border-b border-border pb-3">
                        <div class="min-w-[180px]">
                            <a class="font-medium" href="{{ route('projects.dashboard', $project) }}">{{ $project->name }}</a>
                            @if ($project->code)
                                <div class="text-xs text-muted-foreground">{{ $project->code }}</div>
                            @endif
                        </div>
                        @if ($canEdit)
                            <form method="POST" action="{{ route('briefs.projects.update', [$brief->id, $project->id]) }}" class="flex grow flex-wrap items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <input type="text" name="scope_note" value="{{ $project->pivot->scope_note }}" placeholder="{{ __('ui.brief_scope_note') }}" class="kt-input grow" />
                                <x-button type="submit" color="outline" size="sm">{{ __('ui.brief_save_note') }}</x-button>
                            </form>
                            <form method="POST" action="{{ route('briefs.projects.destroy', [$brief->id, $project->id]) }}">
                                @csrf
                                @method('DELETE')
                                <x-button type="submit" color="ghost" size="sm">{{ __('ui.brief_unlink') }}</x-button>
                            </form>
                        @else
                            <p class="grow text-sm text-muted-foreground whitespace-pre-line">{{ $project->pivot->scope_note }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @if ($canEdit && $candidates->isNotEmpty())
            <form method="POST" action="{{ route('briefs.projects.store', $brief->id) }}" class="flex flex-wrap items-end gap-3 mb-6">
                @csrf
                <div class="kt-form-item">
                    <label class="kt-form-label" for="link_project_id">{{ __('ui.brief_link_project') }}</label>
                    <select id="link_project_id" name="project_id" @foreach ($selectAttrs as $attr => $attrValue) {{ $attr }}="{{ $attrValue }}" @endforeach>
                        @foreach ($candidates as $candidate)
                            <option value="{{ $candidate->id }}">{{ $candidate->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="kt-form-item grow">
                    <label class="kt-form-label" for="link_scope_note">{{ __('ui.brief_scope_note') }}</label>
                    <input id="link_scope_note" type="text" name="scope_note" class="kt-input" />
                </div>
                <x-button type="submit" color="primary">{{ __('ui.brief_link') }}</x-button>
            </form>
        @endif

        @if ($canEdit && $brief->isApproved() && entity_can('Project', 'create'))
            <form method="POST" action="{{ route('briefs.create-project', $brief->id) }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div class="kt-form-item grow">
                    <label class="kt-form-label" for="new_project_name">{{ __('ui.brief_project_name') }}</label>
                    <input id="new_project_name" type="text" name="name" value="{{ $brief->title }}" required class="kt-input" />
                </div>
                <div class="kt-form-item">
                    <label class="kt-form-label" for="new_project_code">{{ __('ui.brief_project_code') }}</label>
                    <input id="new_project_code" type="text" name="code" class="kt-input" />
                </div>
                <div class="kt-form-item grow">
                    <label class="kt-form-label" for="new_project_scope">{{ __('ui.brief_scope_note') }}</label>
                    <input id="new_project_scope" type="text" name="scope_note" class="kt-input" />
                </div>
                <x-button type="submit" color="primary">{{ __('ui.brief_create_project') }}</x-button>
            </form>
        @endif
    </x-card>
@endif
