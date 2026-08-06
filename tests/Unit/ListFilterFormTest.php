<?php

namespace Tests\Unit;

use App\Helpers\ListUi;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListFilterFormTest extends TestCase
{
    #[Test]
    public function filter_form_fields_include_catalog_filters_and_skip_idle_relation_drills(): void
    {
        $fields = ListUi::filterFormFields(
            ['workspace_id', 'project_id', 'status_id', 'business_need_id', 'orphans'],
            []
        );

        $names = array_column($fields, 'name');

        $this->assertContains('workspace_id', $names);
        $this->assertContains('project_id', $names);
        $this->assertContains('status_id', $names);
        $this->assertContains('orphans', $names);
        $this->assertNotContains('business_need_id', $names);
    }

    #[Test]
    public function filter_form_fields_surface_active_relation_drills_for_clearing(): void
    {
        $fields = ListUi::filterFormFields(
            ['business_need_id', 'project_id'],
            ['business_need_id' => 42]
        );

        $byName = collect($fields)->keyBy('name');

        $this->assertTrue($byName->has('business_need_id'));
        $this->assertSame('42', $byName['business_need_id']['value']);
        $this->assertNotEmpty($byName['business_need_id']['options']);
    }

    #[Test]
    public function orphans_field_maps_truthy_current_value(): void
    {
        $fields = ListUi::filterFormFields(['orphans'], ['orphans' => 1]);
        $orphans = collect($fields)->firstWhere('name', 'orphans');

        $this->assertNotNull($orphans);
        $this->assertSame('1', $orphans['value']);
    }
}
