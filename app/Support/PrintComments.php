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
    public function __construct(
        public readonly bool $enabled = false,
        protected array $threadsByItem = [],
    ) {}

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
     * @return list<array{thread: Comment, item: Model, number: int}>
     */
    public function collected(): array
    {
        return array_values($this->collected);
    }
}
