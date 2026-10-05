<?php

namespace App\Support;

/**
 * Where a comment thread stands, and so who has to act next.
 *
 *   open         raised, no answer yet                  → the analyst answers
 *   answered     a person replied with a decision       → someone implements it
 *   implemented  the decision was applied               → the analyst verifies
 *   closed       verified and finished (a person only)
 *
 * A person's reply on an implemented or closed thread sends it back to
 * answered. `comments.resolved_at` is still set exactly when a thread is
 * closed, so "not closed" keeps meaning what "open" meant before statuses.
 */
class CommentStatus
{
    public const OPEN = 'open';

    public const ANSWERED = 'answered';

    public const IMPLEMENTED = 'implemented';

    public const CLOSED = 'closed';

    /** Every status except closed, in the order work flows. */
    public const ACTIVE = [self::OPEN, self::ANSWERED, self::IMPLEMENTED];

    /** The requirement is not settled yet: these fail an item's build gate. */
    public const BLOCKING = [self::OPEN, self::ANSWERED];

    /** @return list<string> */
    public static function all(): array
    {
        return [self::OPEN, self::ANSWERED, self::IMPLEMENTED, self::CLOSED];
    }

    public static function tone(string $status): string
    {
        return match ($status) {
            self::OPEN => 'warning',
            self::ANSWERED => 'info',
            self::IMPLEMENTED => 'neutral',
            default => 'success',
        };
    }
}
