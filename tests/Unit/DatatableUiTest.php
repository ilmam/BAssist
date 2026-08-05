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
}
