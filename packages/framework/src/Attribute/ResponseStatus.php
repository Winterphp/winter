<?php

declare(strict_types=1);

namespace Winter\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
final class ResponseStatus
{
    public function __construct(public readonly int $status)
    {
    }
}
