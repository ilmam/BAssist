<?php

namespace Tests\Unit;

use App\Support\ProcessStepSatisfyType;
use PHPUnit\Framework\TestCase;

class ProcessStepSatisfyTypeTest extends TestCase
{
    public function test_encode_and_decode_round_trip(): void
    {
        $encoded = ProcessStepSatisfyType::encode(ProcessStepSatisfyType::FEATURE, 7);

        $this->assertSame('feature:7', $encoded);
        $this->assertSame(
            ['type' => 'feature', 'id' => 7],
            ProcessStepSatisfyType::decode($encoded)
        );
    }

    public function test_decode_rejects_invalid_values(): void
    {
        $this->assertSame(['type' => null, 'id' => null], ProcessStepSatisfyType::decode(''));
        $this->assertSame(['type' => null, 'id' => null], ProcessStepSatisfyType::decode('feature'));
        $this->assertSame(['type' => null, 'id' => null], ProcessStepSatisfyType::decode('unknown:1'));
        $this->assertSame(['type' => null, 'id' => null], ProcessStepSatisfyType::decode('feature:0'));
    }
}
