<?php

namespace App\Support;

/**
 * How a brief reached us.
 */
final class BriefSourceType
{
    public const EMAIL = 'email';

    public const MEETING = 'meeting';

    public const DOCUMENT = 'document';

    public const OTHER = 'other';

    /** @return list<string> */
    public static function values(): array
    {
        return [self::EMAIL, self::MEETING, self::DOCUMENT, self::OTHER];
    }

    /** @return array<string, string> */
    public static function selectOptions(): array
    {
        return [
            self::EMAIL => __('ui.brief_source_email'),
            self::MEETING => __('ui.brief_source_meeting'),
            self::DOCUMENT => __('ui.brief_source_document'),
            self::OTHER => __('ui.brief_source_other'),
        ];
    }

    public static function label(string $code): string
    {
        return self::selectOptions()[$code] ?? $code;
    }
}
