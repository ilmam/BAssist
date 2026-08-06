<?php

namespace Tests\Unit;

use App\Helpers\DatatableUi;
use App\Helpers\ListUi;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DatatableUiTest extends TestCase
{
    #[Test]
    public function identity_column_has_no_fixed_width_so_it_absorbs_leftover_space(): void
    {
        $style = DatatableUi::columnStyle('name', 0);

        // No bare `width:` — under table-layout: fixed that would opt the column
        // out of the "auto" leftover-space share. A `min-width` floor is fine:
        // it doesn't affect that eligibility, only sets a lower bound.
        $this->assertDoesNotMatchRegularExpression('/(?<!min-)width\s*:/', $style);
        $this->assertStringContainsString('min-width: '.DatatableUi::IDENTITY_MIN_WIDTH, $style);
    }

    #[Test]
    public function title_after_code_also_stays_flexible(): void
    {
        $style = DatatableUi::columnStyle('title', 1);

        $this->assertDoesNotMatchRegularExpression('/(?<!min-)width\s*:/', $style);
        $this->assertStringContainsString('min-width: '.DatatableUi::IDENTITY_MIN_WIDTH, $style);
    }

    #[Test]
    public function explicit_identity_width_style_opts_out_of_leftover_absorption(): void
    {
        $style = DatatableUi::columnStyle([
            'data' => 'title',
            'style' => DatatableUi::identityWidthStyle(),
        ], 1);

        $this->assertStringContainsString('width: '.DatatableUi::IDENTITY_WIDTH, $style);
        $this->assertStringContainsString('min-width: '.DatatableUi::IDENTITY_MIN_WIDTH, $style);
    }

    #[Test]
    public function plain_data_column_arrays_are_not_treated_as_custom(): void
    {
        $this->assertFalse(DatatableUi::isCustomColumn([
            'data' => 'title',
            'style' => DatatableUi::identityWidthStyle(),
        ]));
        $this->assertSame('title', DatatableUi::columnDataField([
            'data' => 'title',
            'style' => DatatableUi::identityWidthStyle(),
        ]));
        $this->assertNull(DatatableUi::columnDataField([
            'custom' => true,
            'name' => 'score_label',
            'template' => '{score_label}',
        ]));
    }

    #[Test]
    public function identity_column_min_width_floor_still_wraps_not_nowrap(): void
    {
        // Wide Risks-style header (many later columns) — identity must still
        // keep its floor and stay wrap-able, never forced nowrap.
        $style = DatatableUi::columnStyle('title', 1);
        $headerStyle = DatatableUi::headerStyle($style);

        $this->assertStringNotContainsString('white-space: nowrap', $style);
        $this->assertStringNotContainsString('white-space: nowrap', $headerStyle);
    }

    #[Test]
    public function relation_column_gets_explicit_width_and_allows_wrap(): void
    {
        $style = DatatableUi::columnStyle('workspace.name', 1);

        $this->assertStringContainsString('width: '.DatatableUi::RELATION_WIDTH, $style);
        $this->assertStringNotContainsString('white-space: nowrap', $style);
    }

    #[Test]
    public function status_column_gets_explicit_width_and_allows_wrap(): void
    {
        $style = DatatableUi::columnStyle('status.name', 2);

        $this->assertStringContainsString('width: '.DatatableUi::SHORT_WIDTH, $style);
        $this->assertStringNotContainsString('white-space: nowrap', $style);
    }

    #[Test]
    public function count_columns_stay_nowrap_with_explicit_width(): void
    {
        $col = ListUi::childLinkColumn('BusinessObjective', 'project_id', 'business_objectives_count', [
            'title' => 'Business Objectives',
        ]);

        $style = DatatableUi::columnStyle($col, 3);

        $this->assertStringContainsString('width: '.DatatableUi::COUNT_WIDTH, $style);
        $this->assertStringContainsString('white-space: nowrap', $style);
        $this->assertStringNotContainsString('white-space: nowrap', DatatableUi::headerStyle($style));
    }

    #[Test]
    public function risk_enum_columns_get_compact_short_width_not_relation_width(): void
    {
        // Risks has far more columns than other lists (code, title, project,
        // category, likelihood, impact, score, response, owner, status,
        // actions). These enum-style fields must stay at SHORT_WIDTH — not
        // the wider RELATION_WIDTH catch-all — or the total column-width sum
        // crushes the width-less title column under table-layout: fixed.
        foreach (['category', 'likelihood', 'impact', 'response', 'owner'] as $index => $root) {
            $style = DatatableUi::columnStyle($root, $index + 2);

            $this->assertStringContainsString('width: '.DatatableUi::SHORT_WIDTH, $style, "root [{$root}] should use SHORT_WIDTH");
            $this->assertStringNotContainsString(DatatableUi::RELATION_WIDTH, $style, "root [{$root}] should not use RELATION_WIDTH");
        }
    }

    #[Test]
    public function actions_column_is_compact(): void
    {
        $style = DatatableUi::actionsStyle([
            ['action' => 'show'],
            ['action' => 'edit', 'menu' => true],
            ['action' => 'delete'],
        ]);

        // 4 slots × 2.1rem = 8.4rem
        $this->assertStringContainsString('width: 8.4rem', $style);
        $this->assertStringContainsString('min-width: 8.4rem', $style);
    }

    #[Test]
    public function collapsed_actions_column_is_single_slot_width(): void
    {
        $style = DatatableUi::actionsStyle([
            ['action' => 'show'],
            ['action' => 'edit', 'menu' => true],
            ['action' => 'delete'],
        ], collapsed: true);

        $this->assertStringContainsString('width: 3.75rem', $style);
        $this->assertStringContainsString('min-width: 3.75rem', $style);
    }

    #[Test]
    public function change_request_enum_columns_get_compact_short_width_not_relation_width(): void
    {
        // impact_level (enum) and requestor (person name) must stay at
        // SHORT_WIDTH — same treatment as Risk's owner — or they add to the
        // column-width pressure that crushes the width-less title column.
        foreach (['impact_level', 'requestor'] as $index => $root) {
            $style = DatatableUi::columnStyle($root, $index + 3);

            $this->assertStringContainsString('width: '.DatatableUi::SHORT_WIDTH, $style, "root [{$root}] should use SHORT_WIDTH");
            $this->assertStringNotContainsString(DatatableUi::RELATION_WIDTH, $style, "root [{$root}] should not use RELATION_WIDTH");
        }
    }

    #[Test]
    public function min_table_width_is_small_on_sparse_lists(): void
    {
        // A typical sparse list: title, status, actions — well under any
        // realistic card width, so `min-width` should never engage.
        $columns = [
            'title',
            'status',
            ['name' => 'actions', 'buttons' => [['action' => 'show'], ['action' => 'edit'], ['action' => 'delete']]],
        ];

        $minWidth = DatatableUi::minTableWidth($columns);

        // title (14) + status (8) + actions (3 slots × 2.1 = 6.3) = 28.3rem
        $this->assertSame('min-width: 28.3rem', $minWidth);
    }

    #[Test]
    public function min_table_width_sums_every_explicit_width_plus_identity_floor_on_wide_lists(): void
    {
        // Risks-style wide list: code, title, project, category, likelihood,
        // impact, response, owner, status, actions.
        $columns = [
            'code', 'title', 'project.name', 'category', 'likelihood',
            'impact', 'response', 'owner', 'status',
            ['name' => 'actions', 'buttons' => [['action' => 'show'], ['action' => 'edit'], ['action' => 'delete']]],
        ];

        $minWidth = DatatableUi::minTableWidth($columns);

        // code(8) + title(min 14) + project(12) + category(8) + likelihood(8)
        // + impact(8) + response(8) + owner(8) + status(8) + actions(6.3) = 88.3rem
        $this->assertSame('min-width: 88.3rem', $minWidth);
    }

    #[Test]
    public function min_table_width_does_not_crush_change_request_title(): void
    {
        // Change Requests: code, title, project, requestor, impact_level,
        // stakeholderNeed, priority, status, actions.
        $columns = [
            'code', 'title', 'project.name', 'requestor', 'impact_level',
            'stakeholderNeed.title', 'priority.name', 'status',
            ['name' => 'actions', 'buttons' => [['action' => 'show'], ['action' => 'edit'], ['action' => 'delete']]],
        ];

        $minWidth = DatatableUi::minTableWidth($columns);

        $this->assertNotSame('', $minWidth);
        $this->assertMatchesRegularExpression('/min-width: \d+(\.\d+)?rem/', $minWidth);

        // The floor guarantees Title always gets at least IDENTITY_MIN_WIDTH
        // once the table is forced to this min-width — confirm the sum is
        // comfortably above every other column's width alone (i.e. Title's
        // floor is actually included, not silently dropped).
        preg_match('/min-width: ([\d.]+)rem/', $minWidth, $matches);
        $totalRem = (float) $matches[1];
        $this->assertGreaterThanOrEqual(14.0 + 8 + 12 + 8 + 12 + 8 + 8 + 6.3, $totalRem);
    }
}
