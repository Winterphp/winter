<?php

declare(strict_types=1);

namespace Winter\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
final class EventListener
{
    public function __construct(public readonly int $priority = 0)
    {
    }
}
