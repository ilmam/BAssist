<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EntityIconTest extends TestCase
{
    #[Test]
    public function it_resolves_model_icons_from_config(): void
    {
        $this->assertSame('focus', entity_icon('BusinessObjective'));
        $this->assertSame('electricity', entity_icon('BusinessNeed'));
        $this->assertSame('shield-cross', entity_icon('Risk'));
        $this->assertSame('message-text', entity_icon('StakeholderNeed'));
        $this->assertSame('lock-2', entity_icon('Constraint'));
        $this->assertSame('scroll', entity_icon('BusinessRule'));
        $this->assertSame('fasten', entity_icon('traceability'));
        $this->assertSame('book', entity_icon('babok_documents'));
        $this->assertSame('file-down', entity_icon('export_pack'));
        $this->assertSame('share', entity_icon('diagrams'));
        $this->assertSame('scroll', entity_icon('guardrails'));
    }

    #[Test]
    public function crud_registry_overlays_nav_icons_from_entity_icons(): void
    {
        $options = \App\Support\CrudEntityRegistry::all()['BusinessObjective'] ?? [];

        $this->assertSame('focus', $options['nav_icon'] ?? null);
        $this->assertSame('focus', $options['nav_icon_v8'] ?? null);
    }

    #[Test]
    public function list_ui_child_links_default_to_entity_icon(): void
    {
        $col = \App\Helpers\ListUi::childLinkColumn('BusinessNeed', 'project_id', 'business_needs_count');

        $this->assertStringContainsString('ki-electricity', $col['template']);
    }
}
