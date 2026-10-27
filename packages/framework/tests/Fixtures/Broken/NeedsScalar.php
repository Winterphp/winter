<?php

declare(strict_types=1);

namespace Winter\Tests\Fixtures\Broken;

final class NeedsScalar
{
    public function __construct(public readonly string $secret)
    {
    }
}
