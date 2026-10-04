<?php

namespace App\View\Components;

use App\Services\AttachmentService;
use App\Services\CommentService;
use App\Support\AttachableSupport;
use App\View\Concerns\ResolvesThemeView;
use Illuminate\View\Component;

class DetailsView extends Component
{
    use ResolvesThemeView;

    /** @var list<\App\Models\Attachment> */
    public array $attachmentRecords = [];

    /** Comment threads (#8) when the entity supports comments; null = no panel. */
    public ?\Illuminate\Support\Collection $commentThreads = null;

    public ?\Illuminate\Support\Collection $mentionUsers = null;

    /** Review state (#8) for approvable items; null = no review bar. */
    public ?array $review = null;

    /** History entries (#8); null = no history panel. */
    public ?\Illuminate\Support\Collection $history = null;

    public function __construct(
        public string $model,
        public object $dto,
        public array $fields,
        public int $columns = 1,
    ) {
        if (AttachableSupport::enabled($model) && isset($dto->id) && (int) $dto->id > 0) {
            $this->attachmentRecords = app(AttachmentService::class)->list($model, (int) $dto->id);
        }

        // Optional features, each switched on by a model attribute (see App\Support\EntityFeatures).
        if (! isset($dto->id) || (int) $dto->id <= 0 || ! entity_can($model, 'view')) {
            return;
        }

        $record = null;
        $resolve = function () use (&$record, $model, $dto) {
            return $record ??= \App\Support\CrudEntityRegistry::repository($model)->findModel((int) $dto->id);
        };

        if (CommentService::supports($model)) {
            $comments = app(CommentService::class);
            $this->commentThreads = $comments->threads($resolve());
            $this->mentionUsers = $comments->tenantUsers();
            if (auth()->user() !== null) {
                $comments->markMentionsSeen($resolve(), auth()->user());
            }
        }

        if (\App\Support\EntityFeatures::tracked($model) || \App\Services\ApprovalService::supports($model)) {
            $this->history = app(\App\Services\ActivityRecorder::class)->historyFor($resolve());
        }

        if (\App\Services\ApprovalService::supports($model)) {
            $approvals = app(\App\Services\ApprovalService::class);
            $this->review = [
                'current' => $approvals->current($resolve()),
                'reset' => $approvals->lastReset($resolve()),
                'canApprove' => $approvals->canApprove($model),
            ];
        }
    }

    public function render()
    {
        return $this->themeView('details-view');
    }
}
