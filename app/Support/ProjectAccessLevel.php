<?php

namespace App\Support;

/**
 * How much a person invited to a project may do there. Ordered: each level
 * includes the ones below it.
 */
enum ProjectAccessLevel: string
{
    case View = 'view';
    case Comment = 'comment';
    case Edit = 'edit';

    public function rank(): int
    {
        return match ($this) {
            self::View => 1,
            self::Comment => 2,
            self::Edit => 3,
        };
    }

    public function atLeast(self $minimum): bool
    {
        return $this->rank() >= $minimum->rank();
    }

    public function label(): string
    {
        return match ($this) {
            self::View => 'Read only',
            self::Comment => 'Read and comment',
            self::Edit => 'Edit',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $level) => $level->value, self::cases());
    }
}
