<?php

declare(strict_types=1);

namespace Winter\Tests\Fixtures\App\Controller;

use Winter\Attribute\Controller;
use Winter\Attribute\GetMapping;
use Winter\Attribute\Route;
use Winter\Tests\Fixtures\App\Exception\BoomException;
use Winter\Tests\Fixtures\App\Exception\TeapotException;

#[Controller]
#[Route('/errors')]
final class ErrorController
{
    #[GetMapping('/boom')]
    public function boom(): array
    {
        throw new BoomException('boom');
    }

    #[GetMapping('/teapot')]
    public function teapot(): array
    {
        throw new TeapotException('teapot');
    }

    #[GetMapping('/generic')]
    public function generic(): array
    {
        throw new \LogicException('generic');
    }
}
