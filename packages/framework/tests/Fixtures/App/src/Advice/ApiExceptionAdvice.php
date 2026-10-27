<?php

declare(strict_types=1);

namespace Winter\Tests\Fixtures\App\Advice;

use Symfony\Component\HttpFoundation\Request;
use Winter\Attribute\ExceptionHandler;
use Winter\Attribute\Order;
use Winter\Attribute\ResponseStatus;
use Winter\Attribute\RestControllerAdvice;
use Winter\Tests\Fixtures\App\Exception\BoomException;
use Winter\Tests\Fixtures\App\Exception\TeapotException;

#[RestControllerAdvice]
#[Order(20)]
final class ApiExceptionAdvice
{
    #[ExceptionHandler(TeapotException::class)]
    #[ResponseStatus(418)]
    #[Order(30)]
    public function teapot(TeapotException $exception, Request $request): array
    {
        return [
            'error' => 'teapot',
            'message' => $exception->getMessage(),
            'path' => $request->getPathInfo(),
        ];
    }

    #[ExceptionHandler(BoomException::class)]
    #[ResponseStatus(503)]
    public function boom(BoomException $exception): array
    {
        return ['error' => 'api', 'message' => $exception->getMessage()];
    }
}
