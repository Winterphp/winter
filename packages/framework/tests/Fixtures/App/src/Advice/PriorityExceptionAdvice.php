<?php

declare(strict_types=1);

namespace Winter\Tests\Fixtures\App\Advice;

use Winter\Attribute\ControllerAdvice;
use Winter\Attribute\ExceptionHandler;
use Winter\Attribute\Order;
use Winter\Attribute\ResponseStatus;
use Winter\Tests\Fixtures\App\Exception\BoomException;

#[ControllerAdvice]
#[Order(1)]
final class PriorityExceptionAdvice
{
    #[ExceptionHandler(BoomException::class)]
    #[ResponseStatus(502)]
    public function boom(BoomException $exception): array
    {
        return ['error' => 'priority', 'message' => $exception->getMessage()];
    }
}
