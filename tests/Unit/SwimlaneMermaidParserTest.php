<?php

namespace Tests\Unit;

use App\Services\SwimlaneMermaidGenerator;
use App\Services\SwimlaneMermaidParser;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SwimlaneMermaidParserTest extends TestCase
{
    public function test_parses_direction_subgraphs_shapes_and_edges(): void
    {
        $parser = new SwimlaneMermaidParser();

        $source = <<<'MERMAID'
swimlane-beta LR
  subgraph Customer ["Customer"]
    RequestReceived(["Request received"])
    SubmitRequest["Submit request"]
  end
  subgraph Support ["Support"]
    Review["Review"]
    Approved{"Approved?"}
    Closed(["Closed"])
  end
  RequestReceived --> SubmitRequest
  SubmitRequest --> Review
  Review --> Approved
  Approved -->|Yes| Closed
  style Customer fill:#9ACCE6,stroke:#5A96B8
MERMAID;

        $parsed = $parser->parse($source);

        $this->assertSame('LR', $parsed['direction']);
        $this->assertSame([
            [
                'lane' => 'Customer',
                'from' => null,
                'type' => 'start',
                'label' => 'Request received',
                'line_title' => null,
            ],
            [
                'lane' => 'Customer',
                'from' => 'Request received',
                'type' => 'process',
                'label' => 'Submit request',
                'line_title' => null,
            ],
            [
                'lane' => 'Support',
                'from' => 'Submit request',
                'type' => 'process',
                'label' => 'Review',
                'line_title' => null,
            ],
            [
                'lane' => 'Support',
                'from' => 'Review',
                'type' => 'decision',
                'label' => 'Approved?',
                'line_title' => null,
            ],
            [
                'lane' => 'Support',
                'from' => 'Approved?',
                'type' => 'end',
                'label' => 'Closed',
                'line_title' => 'Yes',
            ],
        ], $parsed['elements']);
    }

    public function test_ignores_default_start_and_keeps_targets_startless(): void
    {
        $parser = new SwimlaneMermaidParser();

        $source = <<<'MERMAID'
swimlane-beta TB
  subgraph Ops ["Ops"]
    DefaultStart(["Start"])
    Review["Review"]
    Done(["Done"])
  end
  DefaultStart --> Review
  Review --> Done
MERMAID;

        $parsed = $parser->parse($source);

        $this->assertCount(2, $parsed['elements']);
        $this->assertSame([
            'lane' => 'Ops',
            'from' => null,
            'type' => 'process',
            'label' => 'Review',
            'line_title' => null,
        ], $parsed['elements'][0]);
        $this->assertSame('Done', $parsed['elements'][1]['label']);
        $this->assertSame('Review', $parsed['elements'][1]['from']);
        $this->assertSame('end', $parsed['elements'][1]['type']);
        $this->assertFalse(
            collect($parsed['elements'])->contains(fn (array $row) => $row['label'] === 'Start')
        );
    }

    public function test_joins_are_multiple_rows_same_label_different_from(): void
    {
        $parser = new SwimlaneMermaidParser();

        $source = <<<'MERMAID'
swimlane-beta TB
  subgraph A ["A"]
    Left["Left"]
    Right["Right"]
    Join["Join"]
  end
  Left --> Join
  Right -->|alt| Join
MERMAID;

        $parsed = $parser->parse($source);
        $joinRows = array_values(array_filter(
            $parsed['elements'],
            fn (array $row) => $row['label'] === 'Join'
        ));

        $this->assertCount(2, $joinRows);
        $this->assertSame('Left', $joinRows[0]['from']);
        $this->assertNull($joinRows[0]['line_title']);
        $this->assertSame('Right', $joinRows[1]['from']);
        $this->assertSame('alt', $joinRows[1]['line_title']);
    }

    public function test_round_trip_with_generator_preserves_structure(): void
    {
        $generator = new SwimlaneMermaidGenerator();
        $parser = new SwimlaneMermaidParser();

        $elements = [
            ['lane' => 'Customer', 'from' => null, 'type' => 'start', 'label' => 'Request received', 'line_title' => null],
            ['lane' => 'Customer', 'from' => 'Request received', 'type' => 'process', 'label' => 'Submit request', 'line_title' => null],
            ['lane' => 'Support', 'from' => 'Submit request', 'type' => 'process', 'label' => 'Review', 'line_title' => null],
            ['lane' => 'Support', 'from' => 'Review', 'type' => 'decision', 'label' => 'Approved?', 'line_title' => null],
            ['lane' => 'CRM', 'from' => 'Approved?', 'type' => 'process', 'label' => 'Create ticket', 'line_title' => 'Yes'],
            ['lane' => 'Support', 'from' => 'Approved?', 'type' => 'process', 'label' => 'Notify customer', 'line_title' => 'No'],
            ['lane' => 'Support', 'from' => 'Notify customer', 'type' => 'end', 'label' => 'Closed', 'line_title' => null],
        ];

        $mermaid = $generator->generate('Ticket', $elements, 'TB');
        $parsed = $parser->parse($mermaid);

        $this->assertSame('TB', $parsed['direction']);
        $this->assertSame(
            $this->structure($elements),
            $this->structure($parsed['elements'])
        );
    }

    public function test_round_trip_without_explicit_start_drops_default_start(): void
    {
        $generator = new SwimlaneMermaidGenerator();
        $parser = new SwimlaneMermaidParser();

        $elements = [
            ['lane' => 'Ops', 'from' => null, 'type' => 'process', 'label' => 'Review', 'line_title' => null],
            ['lane' => 'Ops', 'from' => 'Review', 'type' => 'end', 'label' => 'Done', 'line_title' => null],
        ];

        $mermaid = $generator->generate(null, $elements, 'LR');
        $this->assertStringContainsString('DefaultStart', $mermaid);

        $parsed = $parser->parse($mermaid);
        $this->assertSame('LR', $parsed['direction']);
        $this->assertSame(
            $this->structure($elements),
            $this->structure($parsed['elements'])
        );
    }

    public function test_parse_error_on_bad_header_does_not_return_elements(): void
    {
        $parser = new SwimlaneMermaidParser();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('swimlane-beta');
        $parser->parse("flowchart TD\n  A --> B\n");
    }

    public function test_parse_error_on_unknown_edge_source(): void
    {
        $parser = new SwimlaneMermaidParser();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing');
        $parser->parse(<<<'MERMAID'
swimlane-beta TB
  subgraph A ["A"]
    One["One"]
  end
  Missing --> One
MERMAID);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{lane: string, from: string|null, type: string, label: string, line_title: string|null}>
     */
    protected function structure(array $rows): array
    {
        return array_map(static function (array $row): array {
            $from = $row['from'] ?? null;
            $from = $from === '' ? null : $from;
            $lineTitle = $row['line_title'] ?? null;
            $lineTitle = $lineTitle === '' ? null : $lineTitle;

            return [
                'lane' => (string) $row['lane'],
                'from' => $from,
                'type' => (string) $row['type'],
                'label' => (string) $row['label'],
                'line_title' => $lineTitle,
            ];
        }, $rows);
    }
}
