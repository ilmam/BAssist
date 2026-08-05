<?php

namespace Tests\Unit;

use App\Support\SolutionPackagingParent;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SolutionPackagingParentTest extends TestCase
{
    public function test_allows_stakeholder_need_only(): void
    {
        $data = SolutionPackagingParent::normalize([
            'stakeholder_need_id' => 3,
            'change_request_id' => null,
        ]);

        $this->assertSame(3, $data['stakeholder_need_id']);
        $this->assertNull($data['change_request_id']);
    }

    public function test_allows_change_request_only(): void
    {
        $data = SolutionPackagingParent::normalize([
            'stakeholder_need_id' => 0,
            'change_request_id' => 9,
        ]);

        $this->assertNull($data['stakeholder_need_id']);
        $this->assertSame(9, $data['change_request_id']);
    }

    public function test_rejects_both_parents(): void
    {
        $this->expectException(ValidationException::class);

        SolutionPackagingParent::normalize([
            'stakeholder_need_id' => 3,
            'change_request_id' => 9,
        ]);
    }

    public function test_allows_neither_parent(): void
    {
        $data = SolutionPackagingParent::normalize([
            'stakeholder_need_id' => null,
            'change_request_id' => null,
        ]);

        $this->assertNull($data['stakeholder_need_id']);
        $this->assertNull($data['change_request_id']);
    }
}
