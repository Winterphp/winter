<?php

declare(strict_types=1);

namespace Winter\Tests\Fixtures\App\Service;

use Winter\Attribute\Profile;
use Winter\Attribute\Service;

#[Service]
#[Profile('test')]
final class TestOnlyService
{
}
