<?php

namespace Tests\Unit;

use App\Services\SaltScreenAssembler;
use PHPUnit\Framework\TestCase;

class SaltScreenAssemblerTest extends TestCase
{
    public function test_orders_by_position_and_joins_elements_sharing_a_row_hint(): void
    {
        $salt = (new SaltScreenAssembler)->assemble('Bid', [
            ['position' => 30, 'row' => 1, 'kind' => 'button', 'label' => '+100K'],
            ['position' => 10, 'row' => null, 'kind' => 'label', 'label' => 'Highest bid'],
            ['position' => 20, 'row' => 1, 'kind' => 'button', 'label' => '+50K'],
            ['position' => 40, 'row' => null, 'kind' => 'input', 'label' => 'Amount'],
        ]);

        $this->assertSame(
            "@startsalt\n{+\n  <b>Bid\n  --\n  Highest bid\n  [+50K] | [+100K]\n  \"Amount        \"\n}\n@endsalt",
            $salt,
        );
    }

    public function test_labels_cannot_inject_salt_syntax(): void
    {
        $salt = (new SaltScreenAssembler)->assemble('T', [
            ['position' => 1, 'kind' => 'label', 'label' => "a | b\n}"],
        ]);

        $this->assertStringContainsString("  a b\n}", $salt);
        $this->assertStringNotContainsString('a |', $salt);
    }

    public function test_containers_nest_into_panels_columns_and_a_table(): void
    {
        $salt = (new SaltScreenAssembler)->assemble('Bid', [
            ['key' => 'cols', 'position' => 0, 'kind' => 'columns', 'label' => ''],
            ['key' => 'a', 'parent_key' => 'cols', 'position' => 1, 'kind' => 'panel', 'label' => 'Left'],
            ['key' => 'a1', 'parent_key' => 'a', 'position' => 2, 'kind' => 'label', 'label' => 'Time left'],
            ['key' => 'b', 'parent_key' => 'cols', 'position' => 3, 'kind' => 'panel', 'label' => 'Right'],
            ['key' => 'b1', 'parent_key' => 'b', 'position' => 4, 'kind' => 'button', 'label' => 'Go'],
            ['key' => 't', 'position' => 5, 'kind' => 'table', 'label' => ''],
            ['key' => 't1', 'parent_key' => 't', 'position' => 6, 'kind' => 'tablerow', 'label' => 'Name | Status'],
            ['key' => 't2', 'parent_key' => 't', 'position' => 7, 'kind' => 'tablerow', 'label' => 'One | Draft'],
        ]);

        $this->assertSame(implode("\n", [
            '@startsalt',
            '{+',
            '  <b>Bid',
            '  --',
            '  {',
            '    {+',
            '      <b>Left',
            '      --',
            '      Time left',
            '    } | {+',
            '      <b>Right',
            '      --',
            '      [Go]',
            '    }',
            '  }',
            '  {#',
            '    <b>Name | <b>Status',
            '    One | Draft',
            '  }',
            '}',
            '@endsalt',
        ]), $salt);
    }

    public function test_normalize_keeps_a_parent_only_when_it_is_an_earlier_container(): void
    {
        $rows = (new SaltScreenAssembler)->normalizeRows([
            ['key' => 'p', 'kind' => 'panel', 'label' => 'P'],
            ['key' => 'x', 'parent_key' => 'p', 'kind' => 'label', 'label' => 'inside'],
            ['key' => 'y', 'parent_key' => 'x', 'kind' => 'label', 'label' => 'under a leaf'],
            ['key' => 'z', 'parent_key' => 'later', 'kind' => 'label', 'label' => 'forward ref'],
            ['key' => 'later', 'kind' => 'panel', 'label' => ''],
        ]);

        // A leaf cannot hold rows and a container that comes later cannot be a parent yet.
        $this->assertSame([null, 'p', null, null, null], array_column($rows, 'parent_key'));
    }
}
