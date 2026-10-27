<?php

declare(strict_types=1);

namespace Winter\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Component
{
    public function __construct(public readonly ?string $name = null)
    {
    }
}
