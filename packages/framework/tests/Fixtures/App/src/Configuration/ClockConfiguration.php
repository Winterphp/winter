<?php

declare(strict_types=1);

namespace Winter\Tests\Fixtures\App\Configuration;

use Winter\Attribute\Bean;
use Winter\Attribute\Configuration;

#[Configuration]
final class ClockConfiguration
{
    #[Bean]
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-01-01 00:00:00');
    }
}
