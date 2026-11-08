{{-- Where this project came from. Many projects start without a brief; that is normal and never flagged. --}}
@if (entity_can('Brief', 'view'))
    @php
        $briefs = $project->briefs;
        $canLink = entity_can('Brief', 'update');
        $linkedIds = $briefs->pluck('id')->all();
        $candidates = $canLink
            ? \App\Models\Brief::query()->where('workspace_id', $project->workspace_id)->whereNotIn('id', $linkedIds)->orderBy('title')->get(['id', 'title'])
            : collect();
        [$selectAttrs] = ui_form_select_attrs();
    @endphp

    @if ($briefs->isNotEmpty() || $candidates->isNotEmpty())
        <x-card title="{{ __('ui.brief_from_brief') }}">
            @if ($errors->any())
                <div class="kt-alert kt-alert-destructive mb-5">
                    <ul class="list-disc ps-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @forelse ($briefs as $brief)
                <div class="flex flex-wrap items-start gap-3 border-b border-border pb-3 mb-3">
                    <div class="min-w-[180px]">
                        <a class="font-medium" href="{{ route('briefs.show', $brief->id) }}">{{ $brief->title }}</a>
                        <div class="text-xs text-muted-foreground">{{ __('ui.brief_version') }} {{ $brief->version }} · {{ $brief->statusLabel() }}</div>
                    </div>
                    <p class="grow text-sm text-muted-foreground whitespace-pre-line">{{ $brief->pivot->scope_note }}</p>
                    @if ($canLink)
                        <form method="POST" action="{{ route('briefs.projects.destroy', [$brief->id, $project->id]) }}">
                            @csrf
                            @method('DELETE')
                            <x-button type="submit" color="ghost" size="sm">{{ __('ui.brief_unlink') }}</x-button>
                        </form>
                    @endif
                </div>
            @empty
                <p class="text-sm text-muted-foreground mb-4">{{ __('ui.brief_from_brief_empty') }}</p>
            @endforelse

            @if ($candidates->isNotEmpty())
                <form method="POST" action="{{ route('projects.briefs.store', $project) }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="kt-form-item">
                        <label class="kt-form-label" for="link_brief_id">{{ __('ui.brief_link_existing') }}</label>
                        <select id="link_brief_id" name="brief_id" @foreach ($selectAttrs as $attr => $attrValue) {{ $attr }}="{{ $attrValue }}" @endforeach>
                            @foreach ($candidates as $candidate)
                                <option value="{{ $candidate->id }}">{{ $candidate->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="kt-form-item grow">
                        <label class="kt-form-label" for="brief_scope_note">{{ __('ui.brief_scope_note') }}</label>
                        <input id="brief_scope_note" type="text" name="scope_note" class="kt-input" />
                    </div>
                    <x-button type="submit" color="primary">{{ __('ui.brief_link') }}</x-button>
                </form>
            @endif
        </x-card>
    @endif
@endif
