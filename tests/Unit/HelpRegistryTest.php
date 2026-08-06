<?php

namespace Tests\Unit;

use App\Support\HelpRegistry;
use Tests\TestCase;

class HelpRegistryTest extends TestCase
{
    public function test_key_for_model_uses_plural_snake_resource(): void
    {
        $this->assertSame('business_needs', HelpRegistry::keyForModel('BusinessNeed'));
        $this->assertSame('features', HelpRegistry::keyForModel('Feature'));
        $this->assertSame('strategic_baselines', HelpRegistry::keyForModel('StrategicBaseline'));
    }

    public function test_exists_for_stubbed_and_missing_topics(): void
    {
        $this->assertTrue(HelpRegistry::exists('business_needs'));
        $this->assertTrue(HelpRegistry::existsForModel('BusinessNeed'));
        $this->assertTrue(HelpRegistry::exists('strategic_baselines'));
        $this->assertTrue(HelpRegistry::existsForModel('StrategicBaseline'));
        $this->assertFalse(HelpRegistry::exists('not_a_real_help_topic'));
    }

    public function test_load_parses_frontmatter_title_and_markdown(): void
    {
        $guide = HelpRegistry::load('business_needs');

        $this->assertNotNull($guide);
        $this->assertSame('business_needs', $guide['key']);
        $this->assertSame('Business Needs', $guide['title']);
        $this->assertStringContainsString('<h2>', $guide['html']);
        $this->assertStringContainsString('Problem + Impact', $guide['html']);
        $this->assertStringContainsString('BABOK', $guide['html']);
    }

    public function test_load_strategic_baselines_guide_matches_product_fields(): void
    {
        $guide = HelpRegistry::load('strategic_baselines');

        $this->assertNotNull($guide);
        $this->assertSame('strategic_baselines', $guide['key']);
        $this->assertSame('Strategic Baseline', $guide['title']);
        $this->assertStringContainsString('Current state', $guide['html']);
        $this->assertStringContainsString('Future state', $guide['html']);
        $this->assertStringContainsString('Change strategy', $guide['html']);
        $this->assertStringContainsString('Scope Items', $guide['html']);
        $this->assertStringContainsString('Business Objectives', $guide['html']);
        $this->assertStringNotContainsString('version-locked snapshot', $guide['html']);
    }

    public function test_load_returns_null_when_file_missing(): void
    {
        $this->assertNull(HelpRegistry::load('not_a_real_help_topic'));
    }

    public function test_normalize_key_accepts_hyphenated_topics(): void
    {
        $this->assertSame('business_needs', HelpRegistry::normalizeKey('Business-Needs'));
    }
}
