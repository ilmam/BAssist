<?php

namespace Tests\Unit;

use App\Services\SwimlaneMermaidGenerator;
use PHPUnit\Framework\TestCase;

class SwimlaneMermaidGeneratorTest extends TestCase
{
    public function test_generates_ticket_like_swimlane_with_decision(): void
    {
        $generator = new SwimlaneMermaidGenerator();

        $mermaid = $generator->generate('Support ticket', [
            ['lane' => 'Customer', 'from' => null, 'type' => 'start', 'label' => 'Request received', 'line_title' => null],
            ['lane' => 'Customer', 'from' => 'Request received', 'type' => 'process', 'label' => 'Submit request', 'line_title' => null],
            ['lane' => 'Support', 'from' => 'Submit request', 'type' => 'process', 'label' => 'Review', 'line_title' => null],
            ['lane' => 'Support', 'from' => 'Review', 'type' => 'decision', 'label' => 'Approved?', 'line_title' => null],
            ['lane' => 'CRM', 'from' => 'Approved?', 'type' => 'process', 'label' => 'Create ticket', 'line_title' => 'Yes'],
            ['lane' => 'Support', 'from' => 'Approved?', 'type' => 'process', 'label' => 'Notify customer', 'line_title' => 'No'],
            ['lane' => 'Support', 'from' => 'Notify customer', 'type' => 'end', 'label' => 'Closed', 'line_title' => null],
        ], 'TB');

        $this->assertStringStartsWith("swimlane-beta TB\n", $mermaid);
        $this->assertStringNotContainsString('title:', $mermaid);
        $this->assertStringContainsString('subgraph Customer ["Customer"]', $mermaid);
        $this->assertStringContainsString('subgraph Support ["Support"]', $mermaid);
        $this->assertStringContainsString('subgraph Crm ["CRM"]', $mermaid);
        $this->assertStringContainsString('RequestReceived(["Request received"])', $mermaid);
        $this->assertStringContainsString('SubmitRequest["Submit request"]', $mermaid);
        $this->assertStringContainsString('Approved{"Approved?"}', $mermaid);
        $this->assertStringContainsString('Closed(["Closed"])', $mermaid);
        $this->assertStringContainsString('RequestReceived --> SubmitRequest', $mermaid);
        $this->assertStringContainsString('Approved -->|Yes| CreateTicket', $mermaid);
        $this->assertStringContainsString('Approved -->|No| NotifyCustomer', $mermaid);
        $this->assertStringContainsString('NotifyCustomer --> Closed', $mermaid);
    }

    public function test_direction_lr_and_default_tb(): void
    {
        $generator = new SwimlaneMermaidGenerator();

        $lr = $generator->generate(null, [
            ['lane' => 'A', 'type' => 'process', 'label' => 'One'],
        ], 'LR');
        $this->assertStringStartsWith("swimlane-beta LR\n", $lr);

        $tb = $generator->generate(null, [
            ['lane' => 'A', 'type' => 'process', 'label' => 'One'],
        ], 'tb');
        $this->assertStringStartsWith("swimlane-beta TB\n", $tb);
    }

    public function test_lanes_follow_first_appearance_order(): void
    {
        $generator = new SwimlaneMermaidGenerator();

        $mermaid = $generator->generate(null, [
            ['lane' => 'Support', 'type' => 'process', 'label' => 'Review'],
            ['lane' => 'Customer', 'type' => 'start', 'label' => 'Start'],
            ['lane' => 'Support', 'type' => 'end', 'label' => 'Done', 'from' => 'Review'],
        ]);

        $supportPos = strpos($mermaid, 'subgraph Support');
        $customerPos = strpos($mermaid, 'subgraph Customer');

        $this->assertNotFalse($supportPos);
        $this->assertNotFalse($customerPos);
        $this->assertLessThan($customerPos, $supportPos);
    }

    public function test_to_node_id_matches_state_id_style(): void
    {
        $generator = new SwimlaneMermaidGenerator();

        $this->assertSame('RequestReceived', $generator->toNodeId('Request received'));
        $this->assertSame('Approved', $generator->toNodeId('Approved?'));
    }

    public function test_normalize_skips_incomplete_rows(): void
    {
        $generator = new SwimlaneMermaidGenerator();

        $rows = $generator->normalizeElements([
            ['lane' => '', 'type' => 'process', 'label' => 'X'],
            ['lane' => 'A', 'type' => 'process', 'label' => 'Ok', 'from' => 'Prior', 'line_title' => 'Go'],
            ['lane' => 'A', 'type' => 'weird', 'label' => 'Bad'],
        ]);

        $this->assertCount(1, $rows);
        $this->assertSame('A', $rows[0]['lane']);
        $this->assertSame('Ok', $rows[0]['label']);
        $this->assertSame('PS-1', $rows[0]['code']);
        $this->assertNull($rows[0]['stakeholder_need_id']);
    }

    public function test_normalize_preserves_code_and_stakeholder_need(): void
    {
        $generator = new SwimlaneMermaidGenerator();

        $rows = $generator->normalizeElements([
            [
                'id' => 9,
                'lane' => 'Support',
                'type' => 'process',
                'label' => 'Review',
                'code' => 'PS-3',
                'stakeholder_need_id' => 12,
            ],
            [
                'lane' => 'Support',
                'type' => 'start',
                'label' => 'Begin',
                'stakeholder_need_id' => 9,
            ],
        ]);

        $this->assertSame(9, $rows[0]['id']);
        $this->assertSame('PS-3', $rows[0]['code']);
        $this->assertSame(12, $rows[0]['stakeholder_need_id']);
        $this->assertSame('PS-4', $rows[1]['code']);
        $this->assertNull($rows[1]['stakeholder_need_id']);
    }

    public function test_assign_missing_codes_continues_sequence(): void
    {
        $generator = new SwimlaneMermaidGenerator();

        $rows = $generator->normalizeElements([
            ['lane' => 'A', 'type' => 'process', 'label' => 'One', 'code' => 'PS-2'],
            ['lane' => 'A', 'type' => 'process', 'label' => 'Two'],
        ]);

        $this->assertSame('PS-2', $rows[0]['code']);
        $this->assertSame('PS-3', $rows[1]['code']);
    }

    public function test_skips_self_loop_edges(): void
    {
        $generator = new SwimlaneMermaidGenerator();

        $mermaid = $generator->generate(null, [
            ['lane' => 'User', 'from' => 'Start', 'type' => 'process', 'label' => 'Start'],
            ['lane' => 'User', 'from' => 'End', 'type' => 'end', 'label' => 'End'],
            ['lane' => 'User', 'from' => 'Start', 'type' => 'process', 'label' => 'Work'],
        ]);

        $this->assertStringNotContainsString('Start --> Start', $mermaid);
        $this->assertStringNotContainsString('End --> End', $mermaid);
        $this->assertStringContainsString('Start --> Work', $mermaid);
    }

    public function test_quotes_labels_with_parentheses_for_mermaid_safety(): void
    {
        $generator = new SwimlaneMermaidGenerator();

        $mermaid = $generator->generate(null, [
            ['lane' => 'Dealer', 'from' => null, 'type' => 'process', 'label' => 'Provide follow-up (Dealer Responded)'],
            ['lane' => 'Parts Field', 'from' => 'Provide follow-up (Dealer Responded)', 'type' => 'process', 'label' => 'Request more info (TIQ Responded)'],
        ]);

        $this->assertStringContainsString('ProvideFollowUpDealerResponded["Provide follow-up (Dealer Responded)"]', $mermaid);
        $this->assertStringContainsString('RequestMoreInfoTiqResponded["Request more info (TIQ Responded)"]', $mermaid);
        $this->assertStringContainsString(
            'ProvideFollowUpDealerResponded --> RequestMoreInfoTiqResponded',
            $mermaid,
        );
    }

    public function test_emits_lane_style_from_palette_key(): void
    {
        $generator = new SwimlaneMermaidGenerator();

        $mermaid = $generator->generate(null, [
            ['lane' => 'Customer', 'lane_color' => 'blue', 'type' => 'start', 'label' => 'Start'],
            ['lane' => 'Customer', 'lane_color' => '', 'type' => 'process', 'label' => 'Ask', 'from' => 'Start'],
            ['lane' => 'Support', 'lane_color' => 'mint', 'type' => 'process', 'label' => 'Help', 'from' => 'Ask'],
            ['lane' => 'Support', 'type' => 'end', 'label' => 'Done', 'from' => 'Help'],
        ]);

        $this->assertStringContainsString(
            'style Customer fill:#9ACCE6,stroke:#5A96B8',
            $mermaid,
        );
        $this->assertStringContainsString(
            'style Support fill:#BDD8CE,stroke:#6F9A88',
            $mermaid,
        );
    }

    public function test_emits_element_style_from_pastel_palette(): void
    {
        $generator = new SwimlaneMermaidGenerator();

        $mermaid = $generator->generate(null, [
            [
                'lane' => 'Support',
                'lane_color' => 'ice',
                'element_color' => 'peach',
                'type' => 'process',
                'label' => 'Review',
            ],
        ]);

        $this->assertStringContainsString('style Support fill:#E3F3F3,stroke:#8AABB0', $mermaid);
        $this->assertStringContainsString('style Review fill:#FEBA7E,stroke:#CE8341', $mermaid);
    }

    public function test_color_mode_lanes_skips_element_styles(): void
    {
        $generator = new SwimlaneMermaidGenerator();

        $mermaid = $generator->generate(null, [
            [
                'lane' => 'Support',
                'lane_color' => 'mint',
                'element_color' => 'peach',
                'type' => 'process',
                'label' => 'Review',
            ],
        ], 'TB', SwimlaneMermaidGenerator::COLOR_MODE_LANES);

        $this->assertStringContainsString('style Support fill:#BDD8CE,stroke:#6F9A88', $mermaid);
        $this->assertStringNotContainsString('style Review', $mermaid);
    }

    public function test_color_mode_elements_skips_lane_styles(): void
    {
        $generator = new SwimlaneMermaidGenerator();

        $mermaid = $generator->generate(null, [
            [
                'lane' => 'Support',
                'lane_color' => 'mint',
                'element_color' => 'peach',
                'type' => 'process',
                'label' => 'Review',
            ],
        ], 'TB', SwimlaneMermaidGenerator::COLOR_MODE_ELEMENTS);

        $this->assertStringNotContainsString('style Support', $mermaid);
        $this->assertStringContainsString('style Review fill:#FEBA7E,stroke:#CE8341', $mermaid);
    }

    public function test_normalize_color_mode_defaults_to_both(): void
    {
        $this->assertSame('both', SwimlaneMermaidGenerator::normalizeColorMode(null));
        $this->assertSame('both', SwimlaneMermaidGenerator::normalizeColorMode('weird'));
        $this->assertSame('lanes', SwimlaneMermaidGenerator::normalizeColorMode('LANES'));
    }

    public function test_normalize_keeps_valid_lane_color_and_drops_unknown(): void
    {
        $generator = new SwimlaneMermaidGenerator();

        $rows = $generator->normalizeElements([
            ['lane' => 'A', 'lane_color' => 'lilac', 'element_color' => 'rose', 'type' => 'process', 'label' => 'One'],
            ['lane' => 'B', 'lane_color' => 'neon', 'element_color' => 'neon', 'type' => 'process', 'label' => 'Two'],
        ]);

        $this->assertSame('lilac', $rows[0]['lane_color']);
        $this->assertSame('rose', $rows[0]['element_color']);
        $this->assertNull($rows[1]['lane_color']);
        $this->assertNull($rows[1]['element_color']);
    }

    public function test_omits_style_when_lane_has_no_color(): void
    {
        $generator = new SwimlaneMermaidGenerator();

        $mermaid = $generator->generate(null, [
            ['lane' => 'Ops', 'type' => 'process', 'label' => 'Work'],
        ]);

        $this->assertStringNotContainsString('style Ops', $mermaid);
        $this->assertStringNotContainsString('style Work', $mermaid);
    }

    public function test_color_mode_both_emits_lane_and_element_styles(): void
    {
        $generator = new SwimlaneMermaidGenerator();

        $mermaid = $generator->generate(null, [
            [
                'lane' => 'Support',
                'lane_color' => 'ice',
                'element_color' => 'peach',
                'type' => 'process',
                'label' => 'Review',
            ],
        ], 'TB', 'both');

        $this->assertStringContainsString('style Support fill:#E3F3F3,stroke:#8AABB0', $mermaid);
        $this->assertStringContainsString('style Review fill:#FEBA7E,stroke:#CE8341', $mermaid);
    }
}
