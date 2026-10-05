@extends(ui_layout())

@section('main')
    <x-card title="{{ __('ui.comments_project_page_title', ['project' => $project->name]) }}">
        <x-slot:toolbar>
            <x-button type="link" href="{{ route('projects.dashboard', $project) }}" icon="arrow-left" color="light" size="sm">
                {{ __('ui.project_dashboard') }}
            </x-button>
        </x-slot:toolbar>

        <p class="text-sm text-muted-foreground mb-5">
            {{ trans_choice('ui.comments_project_page_intro', $total, ['count' => $total]) }}
        </p>

        @if ($groups->isEmpty())
            <x-empty-state icon="verify" :title="__('ui.comments_project_page_empty')" compact />
        @else
            <div class="kt-card-table">
                <div class="kt-table-wrapper">
                    <table class="kt-table kt-table-border w-full">
                        <thead>
                            <tr>
                                <th style="width: 28%">{{ __('ui.comments_appendix_item') }}</th>
                                <th>{{ __('ui.comments_appendix_comment') }}</th>
                                <th style="width: 14%">{{ __('ui.comments_appendix_by') }}</th>
                                <th style="width: 9%">{{ __('ui.comments_appendix_age') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($groups as $group)
                                @foreach ($group['threads'] as $thread)
                                    @php $days = (int) $thread->created_at?->diffInDays(now()); @endphp
                                    <tr>
                                        @if ($loop->first)
                                            <td rowspan="{{ $group['threads']->count() }}" class="align-top">
                                                <div class="text-xs text-muted-foreground">
                                                    {{ $group['is_project'] ? __('ui.comments_project_wide') : $group['entity'] }}
                                                </div>
                                                <a href="{{ $group['url'] }}" class="font-medium text-primary">
                                                    <x-code-chip :code="$group['code']" />
                                                    {{ $group['title'] }}
                                                </a>
                                            </td>
                                        @endif
                                        <td class="align-top">
                                            {!! \App\Services\CommentService::render($thread->body) !!}
                                            @if ($thread->replies->isNotEmpty())
                                                <div class="text-xs text-muted-foreground mt-2">
                                                    {{ trans_choice('ui.comments_reply_count', $thread->replies->count(), ['count' => $thread->replies->count()]) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="align-top">
                                            {{ $thread->author?->name ?? __('ui.comments_former_user') }}
                                            @if ($thread->via)
                                                <div class="text-xs text-muted-foreground">({{ __('ui.history_via_'.$thread->via) }})</div>
                                            @endif
                                        </td>
                                        <td class="align-top">{{ trans_choice('ui.comments_age_days', $days, ['count' => $days]) }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </x-card>
@endsection
