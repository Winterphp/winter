<?php

declare(strict_types=1);

namespace Winter\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
final class Bean
{
    public function __construct(public readonly ?string $name = null)
    {
    }
}
