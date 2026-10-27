<?php

declare(strict_types=1);

namespace Winter\Tests\Fixtures\App\Service;

final class ProfiledBean
{
    public function __construct(public readonly string $profile)
    {
    }
}
