<?php

declare(strict_types=1);

namespace Winter\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class ConfigurationProperties
{
    public function __construct(public readonly string $prefix)
    {
    }
}
