@php
    use App\Helpers\Ui;
@endphp

<div class="ba-sections">
@if ($review !== null)
    @include('pages.partials.review-bar', ['reviewModel' => $model, 'reviewRecordId' => (int) ($dto->id ?? 0), 'review' => $review])
@endif

<x-section :title="__('ui.section_details')" icon="document" key="details">
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

<div class="ba-section__sub">
@include('pages.partials.attachments', [
    'model' => $model,
    'recordId' => (int) ($dto->id ?? 0),
    'attachments' => $attachmentRecords ?? [],
    'mode' => 'details',
])
</div>
</x-section>

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
{{-- Related records (children, linked items) passed by the entity view come last: everything above is about this record only. --}}
{{ $slot ?? '' }}
</div>
