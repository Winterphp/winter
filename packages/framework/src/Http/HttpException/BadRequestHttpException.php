<?php

declare(strict_types=1);

namespace Winter\Http\HttpException;

final class BadRequestHttpException extends HttpException
{
    public function __construct(string $message = 'Bad Request', ?\Throwable $previous = null)
    {
        parent::__construct(400, $message, $previous);
    }
}
