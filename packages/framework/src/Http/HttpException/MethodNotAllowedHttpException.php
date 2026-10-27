<?php

declare(strict_types=1);

namespace Winter\Http\HttpException;

final class MethodNotAllowedHttpException extends HttpException
{
    /**
     * @param list<string> $allowed
     */
    public function __construct(array $allowed, string $message = 'Method Not Allowed')
    {
        parent::__construct(405, $message, null, ['Allow' => implode(', ', $allowed)]);
    }
}
