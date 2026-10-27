<?php

declare(strict_types=1);

namespace Winter\Attribute;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class PathVariable
{
    public function __construct(public readonly ?string $name = null)
    {
    }
}
