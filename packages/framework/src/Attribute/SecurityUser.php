<?php

declare(strict_types=1);

namespace Winter\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class SecurityUser
{
    public function __construct(public readonly string $identifier = 'email')
    {
    }
}
