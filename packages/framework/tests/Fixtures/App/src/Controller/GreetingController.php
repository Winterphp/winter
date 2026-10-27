<?php

declare(strict_types=1);

namespace Winter\Tests\Fixtures\App\Controller;

use Winter\Attribute\Controller;
use Winter\Attribute\GetMapping;
use Winter\Attribute\Route;
use Winter\Attribute\Template;
use Winter\Tests\Fixtures\App\Service\GreetingService;

#[Controller]
#[Route('/hello')]
final class GreetingController
{
    public function __construct(private readonly GreetingService $service)
    {
    }

    #[GetMapping('/{name}')]
    public function greet(string $name): array
    {
        return ['message' => $this->service->greet() . ' ' . $name];
    }

    #[GetMapping('')]
    #[Template('hello/index.html.twig')]
    public function page(): array
    {
        return ['message' => $this->service->greet()];
    }
}
