<?php

declare(strict_types=1);

namespace Winter\Tests\Fixtures\App\Controller;

use Winter\Attribute\Controller;
use Winter\Attribute\GetMapping;
use Winter\Attribute\Profile;
use Winter\Attribute\Route;

#[Controller]
#[Profile('prod')]
#[Route('/prod-only')]
final class ProdOnlyController
{
    #[GetMapping('')]
    public function index(): array
    {
        return ['profile' => 'prod'];
    }
}
