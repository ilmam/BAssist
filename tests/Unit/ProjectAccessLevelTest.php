<?php

namespace Tests\Unit;

use App\Support\ProjectAccessLevel;
use PHPUnit\Framework\TestCase;

class ProjectAccessLevelTest extends TestCase
{
    public function test_levels_are_ordered(): void
    {
        $this->assertTrue(ProjectAccessLevel::Edit->atLeast(ProjectAccessLevel::Comment));
        $this->assertTrue(ProjectAccessLevel::Comment->atLeast(ProjectAccessLevel::View));
        $this->assertFalse(ProjectAccessLevel::View->atLeast(ProjectAccessLevel::Comment));
    }

    public function test_values(): void
    {
        $this->assertSame(['view', 'comment', 'edit'], ProjectAccessLevel::values());
    }
}
