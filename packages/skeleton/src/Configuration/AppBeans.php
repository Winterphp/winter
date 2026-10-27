<?php

declare(strict_types=1);

namespace App\Configuration;

use Winter\Attribute\Bean;
use Winter\Attribute\Configuration;

#[Configuration]
final class AppBeans
{
    #[Bean]
    public function clock(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
