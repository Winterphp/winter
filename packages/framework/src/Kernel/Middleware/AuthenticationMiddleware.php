<?php

declare(strict_types=1);

namespace Winter\Kernel\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Winter\Kernel\MiddlewareInterface;
use Winter\Security\JwtAuthenticator;
use Winter\Security\SecurityContext;
use Winter\Security\SessionAuthenticator;

final class AuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly SecurityContext $context,
        private readonly SessionAuthenticator $session,
        private readonly JwtAuthenticator $jwt,
        private readonly string $mode,
    ) {
    }

    public function process(Request $request, callable $next): Response
    {
        $user = null;

        if ($this->mode !== 'jwt') {
            $user = $this->session->authenticate($request);
        }

        if ($user === null && $this->mode !== 'session') {
            $user = $this->jwt->authenticate($request);
        }

        $this->context->setUser($user);

        return $next($request);
    }
}
