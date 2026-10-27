<?php

declare(strict_types=1);

namespace Winter\Http\HttpException;

final class AuthenticationException extends HttpException
{
    public function __construct(
        string $message = 'Authentication required',
        string $scheme = 'Bearer',
        ?\Throwable $previous = null,
    ) {
        parent::__construct(401, $message, $previous, ['WWW-Authenticate' => $scheme]);
    }
}
