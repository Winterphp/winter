<?php

declare(strict_types=1);

namespace Winter\Tests\Fixtures\App\Service;

use Winter\Attribute\Service;
use Winter\Attribute\Value;
use Winter\Tests\Fixtures\App\Formatter\GreetingFormatter;

#[Service]
final class GreetingService
{
    public function __construct(
        private readonly GreetingFormatter $formatter,
        #[Value('app.name', 'Winter')]
        private readonly string $name,
        private readonly \DateTimeImmutable $now,
    ) {
    }

    public function greet(): string
    {
        return $this->formatter->format($this->name);
    }

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }
}
