<?php

namespace Tests\Unit;

use App\Support\PermissionCheck;
use PHPUnit\Framework\TestCase;

class PermissionCheckTest extends TestCase
{
    public function test_can_returns_false_for_unauthenticated_user(): void
    {
        $this->assertFalse(PermissionCheck::can(null, 'create clinical scans'));
    }
}
