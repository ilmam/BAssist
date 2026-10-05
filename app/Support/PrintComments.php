<?php

namespace App\Support;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Model;

/**
 * Per-request collector for printed documents (#8): items ask for their open threads,
 * and the appendix lists exactly the threads that appeared in this document.
 */
class PrintComments
{
    /** @var array<int, array{thread: Comment, item: Model}> */
    protected array $collected = [];

    /**
     * @param  array<string, list<Comment>>  $threadsByItem  "Class:id" => open threads
     */
    /** @var array<string, array{item: Model, approval: ?\App\Models\Approval}> */
    protected array $signoffs = [];

    /**
     * @param  array<string, list<Comment>>  $threadsByItem  "Class:id" => open threads
     * @param  array<string, \App\Models\Approval>|null  $approvals  "Class:id" => current decision; null = no sign-off block
     */
    public function __construct(
        public readonly bool $enabled = false,
        protected array $threadsByItem = [],
        protected ?array $approvals = null,
    ) {}

    /**
     * Sign-off state for an approvable item, or null when the item is not reviewed / sign-off is off.
     *
     * @return array{approval: ?\App\Models\Approval}|null
     */
    public function signoff(mixed $item): ?array
    {
        if ($this->approvals === null || ! $item instanceof Model || ! \App\Services\ApprovalService::supportsRecord($item)) {
            return null;
        }

        $key = $item::class.':'.$item->getKey();
        $this->signoffs[$key] ??= ['item' => $item, 'approval' => $this->approvals[$key] ?? null];

        return ['approval' => $this->signoffs[$key]['approval']];
    }

    /**
     * @return list<array{item: Model, approval: ?\App\Models\Approval}>
     */
    public function signoffs(): array
    {
        return array_values($this->signoffs);
    }

    /**
     * @return list<Comment>
     */
    public function for(mixed $item): array
    {
        if (! $this->enabled || ! $item instanceof Model) {
            return [];
        }

        $threads = $this->threadsByItem[$item::class.':'.$item->getKey()] ?? [];
        foreach ($threads as $thread) {
            $this->collected[$thread->id] ??= ['thread' => $thread, 'item' => $item, 'number' => count($this->collected) + 1];
        }

        return $threads;
    }

    /** Number shown on the margin balloon and in the appendix ("C3"). */
    public function numberOf(Comment $thread): ?int
    {
        return $this->collected[$thread->id]['number'] ?? null;
    }

    /** Whether any open thread exists for the project — reserves the comment margin. */
    public function hasAny(): bool
    {
        return $this->enabled && $this->threadsByItem !== [];
    }

    /**
     * Add every open thread that no printed item has claimed yet, so a document
     * covering the whole project lists all of them. A thread on a record the
     * document does not print (a stakeholder, a change request…) would
     * otherwise be left out. Call it after the items have been rendered.
     */
    public function collectRemaining(): void
    {
        if (! $this->enabled) {
            return;
        }

        foreach ($this->threadsByItem as $threads) {
            foreach ($threads as $thread) {
                $item = $thread->commentable;
                if ($item instanceof Model) {
                    $this->collected[$thread->id] ??= ['thread' => $thread, 'item' => $item, 'number' => count($this->collected) + 1];
                }
            }
        }
    }

    /**
     * @return list<array{thread: Comment, item: Model, number: int}>
     */
    public function collected(): array
    {
        return array_values($this->collected);
    }
}
