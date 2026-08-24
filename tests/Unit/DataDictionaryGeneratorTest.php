<?php

namespace Tests\Unit;

use App\Services\DataDictionaryStubExporter;
use App\Services\DataTypeInference;
use App\Services\EfModelStubExporter;
use App\Services\ErdMermaidGenerator;
use PHPUnit\Framework\TestCase;

class DataDictionaryGeneratorTest extends TestCase
{
    public function test_infers_types_from_field_names(): void
    {
        $inference = new DataTypeInference;

        $this->assertSame('int', $inference->guess('dealer_id'));
        $this->assertSame('datetime', $inference->guess('submitted_at'));
        $this->assertSame('string', $inference->guess('name'));
        $this->assertSame('text', $inference->guess('notes'));
        $this->assertSame('int', $inference->guess('quantity'));
        $this->assertNull($inference->guess('status'));
        $this->assertNull($inference->guess('part_number'));
    }

    public function test_generates_erd_from_captured_fields_only(): void
    {
        $mermaid = (new ErdMermaidGenerator)->generate($this->sampleEntities());

        $this->assertStringStartsWith("erDiagram\n", $mermaid);
        $this->assertStringContainsString('Dealer ||--o{ PartInquiry : "dealer"', $mermaid);
        $this->assertStringContainsString('PartInquiry ||--o{ InquiryLineItem : "inquiry"', $mermaid);
        $this->assertStringContainsString('int dealer_id PK', $mermaid);
        $this->assertStringContainsString('int dealer_id FK', $mermaid);
        $this->assertStringContainsString('string status', $mermaid);
        $this->assertStringContainsString('datetime submitted_at', $mermaid);
        $this->assertStringNotContainsString('MaxLength', $mermaid);
        $this->assertStringNotContainsString('Open', $mermaid);
    }

    public function test_conceptual_erd_omits_fields(): void
    {
        $mermaid = (new ErdMermaidGenerator)->generate($this->sampleEntities(), 'conceptual');

        $this->assertStringStartsWith("erDiagram\n", $mermaid);
        $this->assertStringContainsString('Dealer ||--o{ PartInquiry : "dealer"', $mermaid);
        $this->assertStringContainsString('PartInquiry ||--o{ InquiryLineItem : "inquiry"', $mermaid);
        $this->assertStringContainsString("    Dealer\n", $mermaid);
        $this->assertStringNotContainsString('Dealer {', $mermaid);
        $this->assertStringNotContainsString('dealer_id', $mermaid);
        $this->assertStringNotContainsString('submitted_at', $mermaid);
        $this->assertStringNotContainsString('int ', $mermaid);
    }

    public function test_ef_stub_does_not_invent_length_or_defaults(): void
    {
        $cs = (new EfModelStubExporter)->export('Parts inquiry', $this->sampleEntities());

        $this->assertStringContainsString('public class PartInquiry', $cs);
        $this->assertStringContainsString('public string Status { get; set; } = string.Empty;', $cs);
        $this->assertStringContainsString('public DateTime SubmittedAt { get; set; }', $cs);
        $this->assertStringContainsString('[Key]', $cs);
        $this->assertStringContainsString('Business may not see.', $cs);
        $this->assertStringNotContainsString('MaxLength', $cs);
        $this->assertStringNotContainsString('= "Open"', $cs);
        $this->assertStringNotContainsString('[Table(', $cs);
    }

    public function test_php_stub_does_not_invent_table_names(): void
    {
        $php = (new DataDictionaryStubExporter)->toPhp($this->sampleEntities());

        $this->assertStringContainsString('class PartInquiry', $php);
        $this->assertStringContainsString("'status'", $php);
        $this->assertStringContainsString('return $this->belongsTo(Dealer::class);', $php);
        $this->assertStringNotContainsString('part_inquiries', $php);
        $this->assertStringNotContainsString('MaxLength', $php);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function sampleEntities(): array
    {
        return [
            [
                'name' => 'Dealer',
                'meaning' => 'Selling partner',
                'fields' => [
                    ['name' => 'dealer_id', 'meaning' => 'Identifier', 'type' => '', 'is_pk' => '1', 'references' => ''],
                    ['name' => 'name', 'meaning' => '', 'type' => '', 'is_pk' => '', 'references' => ''],
                    ['name' => 'region', 'meaning' => '', 'type' => 'string', 'is_pk' => '', 'references' => ''],
                ],
            ],
            [
                'name' => 'PartInquiry',
                'meaning' => '',
                'fields' => [
                    ['name' => 'inquiry_id', 'is_pk' => true, 'type' => ''],
                    ['name' => 'dealer_id', 'type' => ''],
                    ['name' => 'submitted_at', 'type' => ''],
                    ['name' => 'status', 'type' => 'string'],
                    ['name' => 'credit_limit', 'type' => 'decimal', 'business_may_see' => false],
                ],
            ],
            [
                'name' => 'InquiryLineItem',
                'fields' => [
                    ['name' => 'item_id', 'is_pk' => true],
                    ['name' => 'inquiry_id'],
                    ['name' => 'part_number', 'type' => 'string'],
                    ['name' => 'quantity'],
                ],
            ],
        ];
    }
}
