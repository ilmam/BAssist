{{-- Review bar (#8, BABOK 5.5): current decision + Approve / Request changes for approvers. --}}
@php
    $current = $review['current'] ?? null;
    $reset = $review['reset'] ?? null;
    $canApprove = (bool) ($review['canApprove'] ?? false);
    $slug = \Illuminate\Support\Str::snake($reviewModel);
    $barId = 'review-'.$slug.'-'.$reviewRecordId;
    $state = $current ? ($current->isApproved() ? 'approved' : 'changes') : ($reset ? 'reset' : 'none');
@endphp

<section class="ba-review ba-review--{{ $state }}" id="{{ $barId }}" data-review-bar data-csrf="{{ csrf_token() }}" data-view-url="{{ model_modal_path($reviewModel, 'view', $reviewRecordId) }}" aria-labelledby="{{ $barId }}-title">
    <div class="ba-review__status">
        <span class="ba-review__icon" aria-hidden="true">
            <i class="ki-filled ki-{{ ['approved' => 'shield-tick', 'changes' => 'information-2', 'reset' => 'arrows-circle', 'none' => 'time'][$state] }}"></i>
        </span>
        <div class="min-w-0">
            <h3 id="{{ $barId }}-title" class="ba-review__title">
                @switch($state)
                    @case('approved') {{ __('ui.review_state_approved') }} @break
                    @case('changes') {{ __('ui.review_state_changes') }} @break
                    @case('reset') {{ __('ui.review_state_reset') }} @break
                    @default {{ __('ui.review_state_none') }}
                @endswitch
            </h3>
            <p class="ba-review__meta">
                @if ($current)
                    {{ __('ui.review_by', ['name' => $current->user?->name ?? '—', 'when' => $current->created_at?->format('Y-m-d H:i')]) }}
                    @if ($current->note) — “{{ $current->note }}” @endif
                @elseif ($reset)
                    {{ __('ui.review_reset_meta', ['name' => $reset->user?->name ?? '—', 'when' => $reset->invalidated_at?->format('Y-m-d H:i')]) }}
                @else
                    {{ __('ui.review_none_meta') }}
                @endif
                <button type="button" class="ba-review__help ba-link-btn" data-help-url="{{ route('help.guide.show', 'collaboration') }}">{{ __('ui.review_how_it_works') }}</button>
            </p>
        </div>
    </div>

    @if ($canApprove)
        <form class="ba-review__form" method="post" data-review-form>
            @csrf
            <label class="sr-only" for="{{ $barId }}-note">{{ __('ui.review_note_label') }}</label>
            <input id="{{ $barId }}-note" name="note" type="text" maxlength="2000" class="kt-input kt-input-sm ba-review__note"
                   placeholder="{{ __('ui.review_note_placeholder') }}"
                   data-required-message="{{ __('ui.review_reason_required') }}">
            <div class="ba-review__actions">
                <button type="submit" class="{{ ui_btn_classes('primary', 'sm') }}"
                        formaction="{{ route('review.approve', ['model' => $slug, 'id' => $reviewRecordId]) }}">
                    <i class="ki-filled ki-check"></i>{{ __('ui.review_approve') }}
                </button>
                <button type="submit" class="{{ ui_btn_classes('outline', 'sm') }}" data-needs-note
                        formaction="{{ route('review.changes', ['model' => $slug, 'id' => $reviewRecordId]) }}">
                    {{ __('ui.review_request_changes') }}
                </button>
            </div>
            <p class="ba-review__error" data-review-error role="alert" hidden></p>
        </form>
    @endif
</section>
