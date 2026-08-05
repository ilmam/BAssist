<?php

namespace App\Helpers;

/**
 * Datatable column layout helpers (widths / nowrap).
 * Kept separate from ListUi (list filters / child links) and general Ui helpers.
 */
class DatatableUi
{
    public const SHORT_WIDTH = '8rem';

    /** Relation label columns (workspace, project, …). */
    public const RELATION_WIDTH = '12rem';

    /** Child-link / count columns: icon + number. */
    public const COUNT_WIDTH = '5.5rem';

    /**
     * Floor for the identity column (name/title), applied as `min-width` only
     * (never `width`). Under table-layout: fixed, only an explicit `width`
     * makes a column ineligible for the "auto" leftover-space share — a bare
     * `min-width` does not, so identity still absorbs 100% of the leftover
     * space on sparse lists (BO/BN/Projects/…) exactly as before. It only
     * kicks in as a real floor on wide lists (e.g. Risks) where the other
     * columns' explicit widths would otherwise leave little/nothing for
     * identity; if that floor can't fit inside the card at 100%, the table
     * overflows and `.kt-table-wrapper` (overflow: auto) scrolls horizontally
     * instead of crushing the title text to an unreadable sliver.
     */
    public const IDENTITY_MIN_WIDTH = '14rem';

    /** Approximate width of one icon button (incl. gap). */
    public const ACTION_SLOT_WIDTH = 2.1;

    /** Minimum actions column width when few/no buttons are known yet. */
    public const ACTIONS_MIN_WIDTH = 5.5;

    /**
     * Root field names that get the compact SHORT_WIDTH instead of the wider
     * RELATION_WIDTH catch-all. Includes short enum-style Risk fields (unique
     * to RiskData/RiskViewData — safe to add here without affecting other
     * entities) so the Risks list, which has far more columns than most
     * lists, doesn't blow past a sane total column-width sum under
     * table-layout: fixed and crush the identity (title) column.
     *
     * @var list<string>
     */
    public const SHORT_ROOTS = [
        'id', 'code', 'number', 'priority', 'status',
        'category', 'likelihood', 'impact', 'response', 'owner',
    ];

    /** @var list<string> */
    public const IDENTITY_ROOTS = ['name', 'title'];

    /** @var list<string> */
    public const RELATION_ROOTS = ['workspace', 'project', 'tenant', 'stakeholder', 'business_need', 'business_objective'];

    /**
     * Resolve header/cell CSS for a datatable column.
     *
     * Layout contract under table-layout: fixed + width: 100%:
     * - Identity (name/title) has NO fixed `width` so leftover space goes there,
     *   only a `min-width` floor (IDENTITY_MIN_WIDTH) so wide tables can't crush it.
     * - Every other column gets an explicit rem width so Status/counts cannot balloon
     *   and the table always fills the card on sparse lists.
     * - Text columns wrap (no nowrap); counts/actions stay nowrap in the body.
     *
     * @param  string|array<string, mixed>  $col
     * @param  int  $index  0-based physical position of this column in the table.
     */
    public static function columnStyle(string|array $col, int $index = -1): string
    {
        $style = is_array($col) ? trim((string) ($col['style'] ?? '')) : '';
        $name = is_array($col)
            ? (string) ($col['data'] ?? $col['name'] ?? '')
            : (string) $col;
        $root = str_contains($name, '.') ? explode('.', $name, 2)[0] : $name;

        if (is_array($col) && (($col['name'] ?? '') === 'actions' || array_key_exists('buttons', $col))) {
            $buttons = is_array($col['buttons'] ?? null) ? $col['buttons'] : [];

            return self::actionsStyle($buttons);
        }

        // Honor an explicit width from the column definition (still strip nowrap for headers).
        if ($style !== '' && preg_match('/(?:min-)?width\s*:/i', $style)) {
            return self::withoutNowrap($style);
        }

        if (in_array($root, self::SHORT_ROOTS, true)) {
            return self::mergeStyle('width: '.self::SHORT_WIDTH, self::withoutNowrap($style));
        }

        if (is_array($col) && (array_key_exists('template', $col) || str_ends_with($name, '_count'))) {
            return self::mergeStyle('width: '.self::COUNT_WIDTH.'; white-space: nowrap', self::withoutNowrap($style));
        }

        // Identity / leading text column: no `width` — absorbs leftover under fixed
        // layout — but a `min-width` floor so wide tables (Risks) can't crush it to
        // an unreadable sliver; see IDENTITY_MIN_WIDTH.
        if ($index === 0 || in_array($root, self::IDENTITY_ROOTS, true)) {
            return self::mergeStyle('min-width: '.self::IDENTITY_MIN_WIDTH, self::withoutNowrap($style));
        }

        if (in_array($root, self::RELATION_ROOTS, true) || $index === 1) {
            return self::mergeStyle('width: '.self::RELATION_WIDTH, self::withoutNowrap($style));
        }

        if ($index > 0) {
            return self::mergeStyle('width: '.self::RELATION_WIDTH, self::withoutNowrap($style));
        }

        return self::withoutNowrap($style);
    }

    /**
     * Actions column: compact control strip; body cells stay nowrap (headers wrap).
     *
     * @param  list<array<string, mixed>|null>  $buttons
     */
    public static function actionsStyle(array $buttons = []): string
    {
        $slots = self::actionButtonSlots($buttons);
        $rem = max(self::ACTIONS_MIN_WIDTH, $slots * self::ACTION_SLOT_WIDTH);

        return 'width: '.$rem.'rem; min-width: '.$rem.'rem; white-space: nowrap';
    }

    /**
     * Header cell style: same sizing, but never nowrap so labels can wrap.
     */
    public static function headerStyle(string $columnStyle): string
    {
        return self::withoutNowrap($columnStyle);
    }

    /**
     * @param  list<array<string, mixed>|null>  $buttons
     */
    public static function actionButtonSlots(array $buttons): int
    {
        $slots = 0;

        foreach ($buttons as $button) {
            if (! is_array($button)) {
                continue;
            }

            $slots += ! empty($button['menu']) ? 2 : 1;
        }

        return max(1, $slots);
    }

    /**
     * Default style for compact custom columns (e.g. child-link counts).
     * Width/nowrap are applied later by columnStyle().
     */
    public static function compactStyle(): string
    {
        return '';
    }

    protected static function withoutNowrap(string $style): string
    {
        $style = trim(preg_replace('/;?\s*white-space\s*:\s*nowrap\s*;?/i', ';', $style) ?? $style, " \t\n\r\0\x0B;");

        return $style;
    }

    protected static function mergeStyle(string $base, string $extra): string
    {
        if ($extra === '') {
            return $base;
        }

        return $base.'; '.$extra;
    }
}
