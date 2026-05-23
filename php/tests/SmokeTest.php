<?php

declare(strict_types=1);

namespace Tests\Smoke;

use PHPUnit\Framework\TestCase;

final class SmokeTest extends TestCase
{
    public function testFrameworkIsOperational(): void
    {
        $this->assertTrue(class_exists(TestCase::class));
    }
}
