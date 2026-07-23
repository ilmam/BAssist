<?php

namespace Tests\Unit;

use App\Services\StateDiagramMermaidGenerator;
use PHPUnit\Framework\TestCase;

class StateDiagramMermaidGeneratorTest extends TestCase
{
    public function test_generates_ticket_lifecycle_diagram(): void
    {
        $generator = new StateDiagramMermaidGenerator();

        $mermaid = $generator->generate('Ticket lifecycle', [
            ['from' => '[*]', 'to' => 'New', 'trigger' => 'Ticket created'],
            ['from' => 'New', 'to' => 'Pending response', 'trigger' => 'Await response'],
            ['from' => 'New', 'to' => 'Closed', 'trigger' => 'Close'],
            ['from' => 'Pending response', 'to' => 'Responded', 'trigger' => 'Response received'],
            ['from' => 'Responded', 'to' => 'Approved', 'trigger' => 'Approve'],
            ['from' => 'Responded', 'to' => 'Rejected', 'trigger' => 'Reject'],
            ['from' => 'Closed', 'to' => '[*]'],
            ['from' => 'Approved', 'to' => '[*]'],
            ['from' => 'Rejected', 'to' => '[*]'],
        ]);

        $this->assertStringStartsWith("stateDiagram-v2\n", $mermaid);
        $this->assertStringNotContainsString('title:', $mermaid);
        $this->assertStringContainsString('PendingResponse : Pending response', $mermaid);
        $this->assertStringContainsString('[*] --> New : Ticket created', $mermaid);
        $this->assertStringContainsString('New --> PendingResponse : Await response', $mermaid);
        $this->assertStringContainsString('Closed --> [*]', $mermaid);
        $this->assertStringContainsString('Approved --> [*]', $mermaid);
        $this->assertStringContainsString('Rejected --> [*]', $mermaid);
    }

    public function test_compose_from_optional_initial_and_finals(): void
    {
        $generator = new StateDiagramMermaidGenerator();

        $rows = $generator->composeFromForm(
            [
                ['from' => 'Still', 'to' => 'Moving', 'trigger' => 'go'],
                ['from' => 'Moving', 'to' => 'Crash'],
            ],
            'Still',
            'Still, Crash'
        );

        $this->assertSame([
            ['from' => '[*]', 'to' => 'Still', 'trigger' => null],
            ['from' => 'Still', 'to' => 'Moving', 'trigger' => 'go'],
            ['from' => 'Moving', 'to' => 'Crash', 'trigger' => null],
            ['from' => 'Still', 'to' => '[*]', 'trigger' => null],
            ['from' => 'Crash', 'to' => '[*]', 'trigger' => null],
        ], $rows);

        $mermaid = $generator->generate(null, [
            ['from' => 'Still', 'to' => 'Moving'],
            ['from' => 'Moving', 'to' => 'Crash'],
        ], 'Still', ['Still', 'Crash']);

        $this->assertStringContainsString('[*] --> Still', $mermaid);
        $this->assertStringContainsString('Still --> [*]', $mermaid);
        $this->assertStringContainsString('Crash --> [*]', $mermaid);
    }

    public function test_split_terminals_extracts_optional_start_and_end(): void
    {
        $generator = new StateDiagramMermaidGenerator();

        $split = $generator->splitTerminals([
            ['from' => '[*]', 'to' => 'Still'],
            ['from' => 'Still', 'to' => 'Moving'],
            ['from' => 'Still', 'to' => '[*]'],
            ['from' => 'Crash', 'to' => '[*]'],
        ]);

        $this->assertSame('Still', $split['initial']);
        $this->assertSame(['Still', 'Crash'], $split['finals']);
        $this->assertSame([
            ['from' => 'Still', 'to' => 'Moving', 'trigger' => null],
        ], $split['transitions']);
    }

    public function test_omitting_initial_and_finals_skips_terminal_shapes(): void
    {
        $generator = new StateDiagramMermaidGenerator();

        $mermaid = $generator->generate(null, [
            ['from' => 'Still', 'to' => 'Moving'],
        ], '', []);

        $this->assertStringNotContainsString('[*]', $mermaid);
        $this->assertStringContainsString('Still --> Moving', $mermaid);
    }

    public function test_to_state_id_preserves_terminal_marker(): void
    {
        $generator = new StateDiagramMermaidGenerator();

        $this->assertSame('[*]', $generator->toStateId('[*]'));
        $this->assertSame('[*]', $generator->toStateId('[start]'));
        $this->assertSame('PendingResponse', $generator->toStateId('Pending response'));
    }
}
