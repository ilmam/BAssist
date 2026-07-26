<?php

namespace App\Helpers;

/**
 * Datatable column layout helpers (widths / nowrap).
 * Kept separate from ListUi (list filters / child links) and general Ui helpers.
 */
class DatatableUi
{
    public const SHORT_WIDTH = '8rem';

    public const COUNT_WIDTH = '10rem';

    /** Approximate width of one icon button (incl. gap). */
    public const ACTION_SLOT_WIDTH = 2.75;

    /** Minimum actions column width when few/no buttons are known yet. */
    public const ACTIONS_MIN_WIDTH = 7.5;

    /** @var list<string> */
    public const SHORT_ROOTS = ['id', 'code', 'number', 'priority', 'status'];

    /**
     * Resolve header/cell CSS for a datatable column.
     *
     * Uses rem widths (Metronic-style), never width: 1%. With table-layout: fixed,
     * columns without a width share the leftover space (e.g. title).
     *
     * @param  string|array<string, mixed>  $col
     */
    public static function columnStyle(string|array $col): string
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

        if ($style !== '' && preg_match('/width\s*:/i', $style)) {
            return $style;
        }

        if (in_array($root, self::SHORT_ROOTS, true)) {
            return self::mergeStyle('width: '.self::SHORT_WIDTH.'; white-space: nowrap', $style);
        }

        if (is_array($col) && (array_key_exists('template', $col) || str_ends_with($name, '_count'))) {
            return self::mergeStyle('width: '.self::COUNT_WIDTH.'; white-space: nowrap', $style);
        }

        return $style;
    }

    /**
     * Actions column width from the buttons that will actually render.
     * Menu/split controls count as two slots (trigger + chevron).
     *
     * @param  list<array<string, mixed>|null>  $buttons
     */
    public static function actionsStyle(array $buttons = []): string
    {
        $slots = self::actionButtonSlots($buttons);
        $rem = max(self::ACTIONS_MIN_WIDTH, $slots * self::ACTION_SLOT_WIDTH);

        return 'width: '.$rem.'rem; white-space: nowrap';
    }

    /**
     * How many horizontal slots the action buttons need.
     *
     * @param  list<array<string, mixed>|null>  $buttons
     */
    public static function actionButtonSlots(array $buttons): int
    {
        $slots = 0;

        foreach ($buttons as $button) {
            if (! is_array($button)) {
                continue;
            }

            // Split/menu control = primary action + dropdown chevron.
            $slots += ! empty($button['menu']) ? 2 : 1;
        }

        return max(1, $slots);
    }

    /**
     * Default style for compact custom columns (e.g. child-link counts).
     * Width is applied later by columnStyle() so layout stays in one place.
     */
    public static function compactStyle(): string
    {
        return 'white-space: nowrap';
    }

    protected static function mergeStyle(string $base, string $extra): string
    {
        if ($extra === '') {
            return $base;
        }

        return $base.'; '.$extra;
    }
}
