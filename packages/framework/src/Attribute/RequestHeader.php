<?php

declare(strict_types=1);

namespace Winter\Attribute;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class RequestHeader
{
    public readonly bool $hasDefault;

    public function __construct(
        public readonly string $name,
        public readonly mixed $default = null,
    ) {
        $this->hasDefault = \func_num_args() > 1;
    }
}
