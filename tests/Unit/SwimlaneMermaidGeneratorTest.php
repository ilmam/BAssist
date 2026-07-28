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
        $this->assertStringContainsString('subgraph Customer [Customer]', $mermaid);
        $this->assertStringContainsString('subgraph Support [Support]', $mermaid);
        $this->assertStringContainsString('subgraph Crm [CRM]', $mermaid);
        $this->assertStringContainsString('RequestReceived([Request received])', $mermaid);
        $this->assertStringContainsString('SubmitRequest[Submit request]', $mermaid);
        $this->assertStringContainsString('Approved{Approved?}', $mermaid);
        $this->assertStringContainsString('Closed([Closed])', $mermaid);
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

        $this->assertSame([
            [
                'lane' => 'A',
                'from' => 'Prior',
                'type' => 'process',
                'label' => 'Ok',
                'line_title' => 'Go',
            ],
        ], $rows);
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
}
