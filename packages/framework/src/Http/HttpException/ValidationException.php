<?php

declare(strict_types=1);

namespace Winter\Http\HttpException;

final class ValidationException extends HttpException
{
    /**
     * @param array<string, list<string>> $errors
     */
    public function __construct(private readonly array $errors, string $message = 'Validation failed')
    {
        parent::__construct(422, $message);
    }

    /**
     * @return array<string, list<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
