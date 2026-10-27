<?php

declare(strict_types=1);

namespace Winter\Kernel\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Winter\Kernel\MiddlewareInterface;

final class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly bool $enabled = true,
        private readonly string $frameOptions = 'SAMEORIGIN',
        private readonly bool $hsts = false,
        private readonly ?string $csp = null,
    ) {
    }

    public function process(Request $request, callable $next): Response
    {
        $response = $next($request);

        if (!$this->enabled) {
            return $response;
        }

        $headers = $response->headers;

        if (!$headers->has('X-Content-Type-Options')) {
            $headers->set('X-Content-Type-Options', 'nosniff');
        }

        if (!$headers->has('X-Frame-Options')) {
            $headers->set('X-Frame-Options', $this->frameOptions);
        }

        if (!$headers->has('Referrer-Policy')) {
            $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }

        if ($this->hsts && $request->isSecure() && !$headers->has('Strict-Transport-Security')) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if ($this->csp !== null && $this->csp !== '' && !$headers->has('Content-Security-Policy')) {
            $headers->set('Content-Security-Policy', $this->csp);
        }

        return $response;
    }
}
