<?php

declare(strict_types=1);

namespace Winter\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class IsGranted
{
    public function __construct(public readonly string $roles)
    {
    }
}
