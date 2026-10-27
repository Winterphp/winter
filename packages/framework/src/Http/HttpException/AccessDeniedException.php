<?php

declare(strict_types=1);

namespace Winter\Http\HttpException;

final class AccessDeniedException extends HttpException
{
    public function __construct(string $message = 'Access denied')
    {
        parent::__construct(403, $message);
    }
}
