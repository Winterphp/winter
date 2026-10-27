<?php

declare(strict_types=1);

namespace Winter\Tests\Fixtures\App\Controller;

use Winter\Attribute\Controller;
use Winter\Attribute\CookieValue;
use Winter\Attribute\GetMapping;
use Winter\Attribute\RequestHeader;
use Winter\Attribute\Route;

#[Controller]
#[Route('/context')]
final class ContextController
{
    #[GetMapping('/headers')]
    public function headers(
        #[RequestHeader('X-Tenant', 'default')] string $tenant,
        #[RequestHeader('X-Retry', '1')] int $retry,
        #[RequestHeader('X-Optional')] ?string $optional = null,
    ): array {
        return ['tenant' => $tenant, 'retry' => $retry, 'optional' => $optional];
    }

    #[GetMapping('/required-header')]
    public function requiredHeader(#[RequestHeader('X-Required')] string $required): array
    {
        return ['required' => $required];
    }

    #[GetMapping('/cookie')]
    public function cookie(
        #[CookieValue('theme', 'light')] string $theme,
        #[CookieValue('count', '0')] int $count,
    ): array {
        return ['theme' => $theme, 'count' => $count];
    }

    #[GetMapping('/required-cookie')]
    public function requiredCookie(#[CookieValue('session')] string $session): array
    {
        return ['session' => $session];
    }
}
