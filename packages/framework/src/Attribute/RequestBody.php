<?php

declare(strict_types=1);

namespace Winter\Attribute;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class RequestBody
{
    public function __construct(public readonly bool $validate = true)
    {
    }
}
