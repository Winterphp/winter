<?php

declare(strict_types=1);

namespace Winter\Tests\Fixtures\App\Event;

final class ThingHappened
{
    public function __construct(public readonly string $name)
    {
    }
}
