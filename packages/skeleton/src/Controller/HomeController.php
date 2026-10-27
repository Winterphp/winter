<?php

declare(strict_types=1);

namespace App\Controller;

use Winter\Attribute\Controller;
use Winter\Attribute\GetMapping;
use Winter\Attribute\Template;
use Winter\Attribute\Value;

#[Controller]
final class HomeController
{
    public function __construct(
        #[Value('app.name', 'Winter')]
        private readonly string $appName,
    ) {
    }

    #[GetMapping('/')]
    #[Template('home/index.html.twig')]
    public function index(): array
    {
        return ['appName' => $this->appName];
    }
}
