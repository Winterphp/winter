<?php

declare(strict_types=1);

namespace Winter\Tests\Fixtures\App\Controller;

use Winter\Attribute\Controller;
use Winter\Attribute\GetMapping;
use Winter\Attribute\IsGranted;
use Winter\Attribute\PostMapping;
use Winter\Attribute\Route;

#[Controller]
#[Route('/admin')]
final class AdminController
{
    #[GetMapping('')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(): array
    {
        return ['ok' => true];
    }

    #[PostMapping('/form')]
    public function form(): array
    {
        return ['submitted' => true];
    }
}
