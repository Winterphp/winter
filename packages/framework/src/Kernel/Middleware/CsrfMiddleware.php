<?php

declare(strict_types=1);

namespace Winter\Kernel\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Winter\Http\HttpException\AccessDeniedException;
use Winter\Kernel\MiddlewareInterface;
use Winter\Security\CsrfService;

final class CsrfMiddleware implements MiddlewareInterface
{
    private const METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function __construct(private readonly CsrfService $csrf)
    {
    }

    public function process(Request $request, callable $next): Response
    {
        if (!\in_array($request->getMethod(), self::METHODS, true)) {
            return $next($request);
        }

        // Les appels JSON sans cookie (API, tests) ne sont pas des soumissions de formulaire.
        if (str_contains((string) $request->headers->get('Content-Type', ''), 'application/json')) {
            return $next($request);
        }

        $token = (string) ($request->request->get('_token') ?? $request->headers->get('X-CSRF-TOKEN', ''));
        $id = (string) $request->headers->get('X-CSRF-ID', 'default');

        if (!$this->csrf->isValid($token, $id)) {
            throw new AccessDeniedException('Invalid CSRF token.');
        }

        return $next($request);
    }
}
