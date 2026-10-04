@php
    use App\Helpers\Ui;
@endphp

<div class="space-y-6">
<div @class([
    'grid gap-x-6 gap-y-4',
    'grid-cols-1' => $columns <= 1,
    'grid-cols-1 md:grid-cols-2' => $columns >= 2,
])>
    @foreach ($fields as $name => $value)
        @php
            $display = is_bool($value)
                ? ($value ? __('ui.yes') : __('ui.no'))
                : trim((string) ($value ?? ''));
            $isEmpty = $display === '';
            $isMultiline = ! $isEmpty && str_contains($display, "\n");
        @endphp
        <div class="kt-form-item">
            <label class="kt-form-label">{{ Ui::fieldLabel((string) $name) }}</label>
            <div
                class="kt-input-view{{ $isMultiline ? ' kt-input-view--multiline' : '' }}"
                aria-readonly="true"
            >@if ($isEmpty)<span class="text-muted-foreground">—</span>@else{{ $display }}@endif</div>
        </div>
    @endforeach
</div>

@include('pages.partials.attachments', [
    'model' => $model,
    'recordId' => (int) ($dto->id ?? 0),
    'attachments' => $attachmentRecords ?? [],
    'mode' => 'details',
])

@if ($review !== null)
    @include('pages.partials.review-bar', ['reviewModel' => $model, 'reviewRecordId' => (int) ($dto->id ?? 0), 'review' => $review])
@endif

@if ($commentThreads !== null)
    @include('pages.partials.comments', [
        'commentModel' => $model,
        'commentRecordId' => (int) ($dto->id ?? 0),
        'threads' => $commentThreads,
        'mentionUsers' => $mentionUsers,
    ])
@endif

@if ($history !== null)
    @include('pages.partials.history', ['history' => $history])
@endif
</div>
