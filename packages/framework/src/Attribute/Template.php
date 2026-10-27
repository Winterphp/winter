<?php

declare(strict_types=1);

namespace Winter\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class Template
{
    public function __construct(
        public readonly string $name,
        public readonly int $status = 200,
    ) {
    }
}
