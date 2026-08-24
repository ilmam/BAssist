<?php

namespace Tests\Feature;

use App\Services\ErdMermaidGenerator;
use Tests\TestCase;

class DataDictionaryDiagramViewTest extends TestCase
{
    public function test_editor_renders_conceptual_and_design_panes(): void
    {
        $entities = [
            [
                'name' => 'Dealer',
                'meaning' => 'Selling partner',
                'fields' => [
                    ['name' => 'dealer_id', 'type' => 'int', 'is_pk' => true, 'references' => ''],
                    ['name' => 'name', 'type' => 'string', 'is_pk' => false, 'references' => ''],
                ],
            ],
            [
                'name' => 'PartInquiry',
                'meaning' => '',
                'fields' => [
                    ['name' => 'inquiry_id', 'type' => 'int', 'is_pk' => true, 'references' => ''],
                    ['name' => 'dealer_id', 'type' => 'int', 'is_pk' => false, 'references' => 'Dealer'],
                ],
            ],
        ];

        $generator = new ErdMermaidGenerator;
        $design = $generator->generate($entities, 'design');
        $conceptual = $generator->generate($entities, 'conceptual');

        $html = view('pages.data_dictionaries.partials.dictionary-editor', [
            'entities' => $entities,
            'editable' => false,
            'autoRender' => true,
            'mermaid' => $design,
            'mermaidConceptual' => $conceptual,
        ])->render();

        $this->assertStringContainsString('data-diagram-tab="conceptual"', $html);
        $this->assertStringContainsString('data-diagram-tab="design"', $html);
        $this->assertStringContainsString('data-diagram-level="conceptual"', $html);
        $this->assertStringContainsString('data-diagram-level="design"', $html);
        $this->assertMatchesRegularExpression('/data-diagram-level="design"[^>]*\bhidden\b/', $html);
        $this->assertStringContainsString(__('ui.conceptual_erd'), $html);
        $this->assertStringContainsString(__('ui.design_erd'), $html);
        $this->assertStringContainsString('Dealer ||--o{ PartInquiry', $html);

        $conceptualPane = $this->paneHtml($html, 'conceptual');
        $designPane = $this->paneHtml($html, 'design');

        $this->assertStringContainsString('Dealer ||--o{ PartInquiry', $conceptualPane);
        $this->assertStringNotContainsString('dealer_id', $conceptualPane);
        $this->assertStringNotContainsString('Dealer {', $conceptualPane);
        $this->assertStringContainsString('int dealer_id PK', $designPane);
        $this->assertStringContainsString('int dealer_id FK', $designPane);
    }

    protected function paneHtml(string $html, string $level): string
    {
        $pattern = '/data-diagram-level="'.preg_quote($level, '/').'"[^>]*>(.*?)data-diagram-level=/s';
        if (preg_match($pattern, $html, $matches) === 1) {
            return $matches[1];
        }

        if ($level === 'design' && preg_match('/data-diagram-level="design"[^>]*>(.*)$/s', $html, $matches) === 1) {
            return $matches[1];
        }

        $this->fail("Missing {$level} diagram pane.");
    }
}
