<?php

declare(strict_types=1);

namespace Winter\Kernel\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Winter\Http\Error\ErrorHandler;
use Winter\Kernel\MiddlewareInterface;

final class ErrorHandlerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ErrorHandler $errors,
        private readonly bool $debug,
    ) {
    }

    public function process(Request $request, callable $next): Response
    {
        try {
            return $next($request);
        } catch (\Throwable $exception) {
            return $this->errors->render($exception, $request, $this->debug);
        }
    }
}
