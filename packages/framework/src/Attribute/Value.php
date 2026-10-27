<?php

declare(strict_types=1);

namespace Winter\Attribute;

#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY)]
final class Value
{
    public readonly bool $hasDefault;

    public function __construct(
        public readonly string $key,
        public readonly mixed $default = null,
    ) {
        $this->hasDefault = \func_num_args() > 1;
    }
}
