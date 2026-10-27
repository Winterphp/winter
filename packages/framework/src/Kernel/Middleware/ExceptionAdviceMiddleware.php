<?php

declare(strict_types=1);

namespace Winter\Kernel\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Winter\Http\ExceptionHandlerResolver;
use Winter\Http\RequestContext;
use Winter\Kernel\MiddlewareInterface;

final class ExceptionAdviceMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly ExceptionHandlerResolver $resolver)
    {
    }

    public function process(Request $request, callable $next): Response
    {
        try {
            return $next($request);
        } catch (\Throwable $exception) {
            $response = $this->resolver->resolve($exception, new RequestContext($request, exception: $exception));

            if ($response !== null) {
                return $response;
            }

            throw $exception;
        }
    }
}
