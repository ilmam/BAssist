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

    public function test_empty_from_maps_to_start_terminal(): void
    {
        $generator = new StateDiagramMermaidGenerator();

        $mermaid = $generator->generate(null, [
            ['from' => '', 'to' => 'Still', 'trigger' => 'begin'],
            ['from' => 'Still', 'to' => 'Moving'],
        ]);

        $this->assertStringContainsString('[*] --> Still : begin', $mermaid);
        $this->assertStringContainsString('Still --> Moving', $mermaid);
        $this->assertStringNotContainsString('State --> Still', $mermaid);
    }

    public function test_empty_to_maps_to_end_terminal(): void
    {
        $generator = new StateDiagramMermaidGenerator();

        $mermaid = $generator->generate(null, [
            ['from' => 'Moving', 'to' => 'Crash'],
            ['from' => 'Crash', 'to' => '', 'trigger' => 'done'],
        ]);

        $this->assertStringContainsString('Moving --> Crash', $mermaid);
        $this->assertStringContainsString('Crash --> [*] : done', $mermaid);
        $this->assertStringNotContainsString('Crash --> State', $mermaid);
    }

    public function test_both_empty_endpoints_are_skipped(): void
    {
        $generator = new StateDiagramMermaidGenerator();

        $mermaid = $generator->generate(null, [
            ['from' => '', 'to' => '', 'trigger' => 'noop'],
            ['from' => 'Still', 'to' => 'Moving'],
        ]);

        $this->assertStringNotContainsString('[*] --> [*]', $mermaid);
        $this->assertStringContainsString('Still --> Moving', $mermaid);
    }

    public function test_empty_from_and_to_on_separate_rows(): void
    {
        $generator = new StateDiagramMermaidGenerator();

        $mermaid = $generator->generate(null, [
            ['from' => '', 'to' => 'Draft', 'trigger' => 'create'],
            ['from' => 'Draft', 'to' => 'Submitted'],
            ['from' => 'Submitted', 'to' => '', 'trigger' => 'archive'],
        ]);

        $this->assertSame(
            "stateDiagram-v2\n    [*] --> Draft : create\n    Draft --> Submitted\n    Submitted --> [*] : archive\n",
            $mermaid
        );
    }

    public function test_start_and_end_keywords_map_to_uml_terminals(): void
    {
        $generator = new StateDiagramMermaidGenerator();

        $mermaid = $generator->generate(null, [
            ['from' => 'start', 'to' => 'Draft', 'trigger' => 'create'],
            ['from' => 'Draft', 'to' => 'Submitted'],
            ['from' => 'Submitted', 'to' => 'end', 'trigger' => 'archive'],
        ]);

        $this->assertSame(
            "stateDiagram-v2\n    [*] --> Draft : create\n    Draft --> Submitted\n    Submitted --> [*] : archive\n",
            $mermaid
        );
        $this->assertStringNotContainsString('Start -->', $mermaid);
        $this->assertStringNotContainsString('--> End', $mermaid);
        $this->assertStringNotContainsString('start :', $mermaid);
        $this->assertStringNotContainsString('end :', $mermaid);
    }

    public function test_star_alias_maps_to_uml_terminals_on_either_side(): void
    {
        $generator = new StateDiagramMermaidGenerator();

        $mermaid = $generator->generate(null, [
            ['from' => '*', 'to' => 'Draft'],
            ['from' => 'Draft', 'to' => '*'],
            ['from' => 'Submitted', 'to' => '*', 'trigger' => 'archive'],
        ]);

        $this->assertStringContainsString('[*] --> Draft', $mermaid);
        $this->assertStringContainsString('Draft --> [*]', $mermaid);
        $this->assertStringContainsString('Submitted --> [*] : archive', $mermaid);
        $this->assertStringNotContainsString('--> *', $mermaid);
        $this->assertStringNotContainsString('* -->', $mermaid);
    }

    public function test_user_examples_all_map_to_bracket_star(): void
    {
        $generator = new StateDiagramMermaidGenerator();

        $mermaid = $generator->generate(null, [
            ['from' => '*', 'to' => 'Draft'],
            ['from' => 'start', 'to' => 'Draft', 'trigger' => 'create'],
            ['from' => 'Draft', 'to' => '*'],
            ['from' => 'Submitted', 'to' => 'end', 'trigger' => 'archive'],
        ]);

        $this->assertSame(
            "stateDiagram-v2\n    [*] --> Draft\n    [*] --> Draft : create\n    Draft --> [*]\n    Submitted --> [*] : archive\n",
            $mermaid
        );
    }

    public function test_start_and_end_keywords_are_case_insensitive(): void
    {
        $generator = new StateDiagramMermaidGenerator();

        $mermaid = $generator->generate(null, [
            ['from' => 'START', 'to' => 'Draft'],
            ['from' => 'Draft', 'to' => ' End '],
        ]);

        $this->assertStringContainsString('[*] --> Draft', $mermaid);
        $this->assertStringContainsString('Draft --> [*]', $mermaid);
        $this->assertStringNotContainsString('Start', $mermaid);
        $this->assertStringNotContainsString('End', $mermaid);
    }

    public function test_does_not_infer_terminals_for_body_only_graph(): void
    {
        $generator = new StateDiagramMermaidGenerator();

        $mermaid = $generator->generate(null, [
            ['from' => 'Draft', 'to' => 'Submitted'],
            ['from' => 'Submitted', 'to' => 'Closed'],
        ]);

        $this->assertStringNotContainsString('[*] --> Draft', $mermaid);
        $this->assertStringNotContainsString('Closed --> [*]', $mermaid);
        $this->assertStringContainsString('Draft --> Submitted', $mermaid);
        $this->assertStringContainsString('Submitted --> Closed', $mermaid);
    }

    public function test_empty_initial_and_finals_do_not_infer_graph_sources_sinks(): void
    {
        $generator = new StateDiagramMermaidGenerator();

        $mermaid = $generator->generate(null, [
            ['from' => 'Still', 'to' => 'Moving'],
            ['from' => 'Moving', 'to' => 'Crash'],
        ], '', []);

        $this->assertStringNotContainsString('[*] --> Still', $mermaid);
        $this->assertStringNotContainsString('Crash --> [*]', $mermaid);
        $this->assertStringContainsString('Still --> Moving', $mermaid);
        $this->assertStringContainsString('Moving --> Crash', $mermaid);
    }

    public function test_compose_keeps_blank_endpoint_transitions_with_triggers(): void
    {
        $generator = new StateDiagramMermaidGenerator();

        $rows = $generator->composeFromForm(
            [
                ['from' => '', 'to' => 'Still', 'trigger' => 'begin'],
                ['from' => 'Still', 'to' => 'Crash'],
                ['from' => 'Crash', 'to' => '', 'trigger' => 'end'],
            ],
            null,
            ''
        );

        $this->assertSame([
            ['from' => '[*]', 'to' => 'Still', 'trigger' => 'begin'],
            ['from' => 'Still', 'to' => 'Crash', 'trigger' => null],
            ['from' => 'Crash', 'to' => '[*]', 'trigger' => 'end'],
        ], $rows);
    }

    public function test_split_keeps_triggered_terminal_edges_as_start_end_keywords(): void
    {
        $generator = new StateDiagramMermaidGenerator();

        $split = $generator->splitTerminals([
            ['from' => '[*]', 'to' => 'New', 'trigger' => 'Ticket created'],
            ['from' => 'New', 'to' => 'Closed'],
            ['from' => 'Closed', 'to' => '[*]', 'trigger' => 'Archive'],
        ]);

        $this->assertNull($split['initial']);
        $this->assertSame([], $split['finals']);
        $this->assertSame([
            ['from' => 'start', 'to' => 'New', 'trigger' => 'Ticket created'],
            ['from' => 'New', 'to' => 'Closed', 'trigger' => null],
            ['from' => 'Closed', 'to' => 'end', 'trigger' => 'Archive'],
        ], $split['transitions']);
    }

    public function test_split_recognizes_start_and_end_keywords(): void
    {
        $generator = new StateDiagramMermaidGenerator();

        $split = $generator->splitTerminals([
            ['from' => 'start', 'to' => 'Still'],
            ['from' => 'Still', 'to' => 'Moving'],
            ['from' => 'Crash', 'to' => 'end'],
        ]);

        $this->assertSame('Still', $split['initial']);
        $this->assertSame(['Crash'], $split['finals']);
        $this->assertSame([
            ['from' => 'Still', 'to' => 'Moving', 'trigger' => null],
        ], $split['transitions']);
    }

    public function test_to_state_id_preserves_terminal_marker(): void
    {
        $generator = new StateDiagramMermaidGenerator();

        $this->assertSame('[*]', $generator->toStateId('[*]'));
        $this->assertSame('[*]', $generator->toStateId('[start]'));
        $this->assertSame('[*]', $generator->toStateId(''));
        $this->assertSame('[*]', $generator->toStateId('start'));
        $this->assertSame('[*]', $generator->toStateId('END'));
        $this->assertSame('[*]', $generator->toStateId('*'));
        $this->assertSame('PendingResponse', $generator->toStateId('Pending response'));
    }

    public function test_to_editor_rows_shows_star_for_terminals(): void
    {
        $generator = new StateDiagramMermaidGenerator();

        $rows = $generator->toEditorRows([
            ['from' => '[*]', 'to' => 'Draft', 'trigger' => 'create'],
            ['from' => 'Draft', 'to' => 'Submitted'],
            ['from' => 'Submitted', 'to' => 'end', 'trigger' => 'archive'],
            ['from' => 'start', 'to' => 'Draft'],
            ['from' => 'Closed', 'to' => ''],
        ]);

        $this->assertSame([
            ['from' => '*', 'to' => 'Draft', 'trigger' => 'create'],
            ['from' => 'Draft', 'to' => 'Submitted', 'trigger' => null],
            ['from' => 'Submitted', 'to' => '*', 'trigger' => 'archive'],
            ['from' => '*', 'to' => 'Draft', 'trigger' => null],
            ['from' => 'Closed', 'to' => '*', 'trigger' => null],
        ], $rows);
    }

    public function test_empty_string_initial_finals_use_transition_terminals_only(): void
    {
        $generator = new StateDiagramMermaidGenerator();

        $mermaid = $generator->generate(null, [
            ['from' => '*', 'to' => 'Draft'],
            ['from' => 'Draft', 'to' => 'end'],
        ], '', '');

        $this->assertSame(
            "stateDiagram-v2\n    [*] --> Draft\n    Draft --> [*]\n",
            $mermaid
        );
    }
}
