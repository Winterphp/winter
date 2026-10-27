<?php

declare(strict_types=1);

namespace Winter\Kernel;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class MiddlewareDispatcher
{
    /**
     * @param list<MiddlewareInterface> $middleware
     */
    public function __construct(private readonly array $middleware)
    {
    }

    /**
     * @param callable(Request): Response $core
     */
    public function handle(Request $request, callable $core): Response
    {
        $next = $core;

        foreach (array_reverse($this->middleware) as $middleware) {
            $next = static fn (Request $current): Response => $middleware->process($current, $next);
        }

        return $next($request);
    }
}
