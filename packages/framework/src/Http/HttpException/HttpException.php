<?php

declare(strict_types=1);

namespace Winter\Http\HttpException;

class HttpException extends \RuntimeException
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly int $statusCode,
        string $message = '',
        ?\Throwable $previous = null,
        private readonly array $headers = [],
    ) {
        parent::__construct($message !== '' ? $message : 'HTTP error ' . $statusCode, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
