<?php

declare(strict_types=1);

namespace Winter\Validation;

use Symfony\Component\Validator\ConstraintViolationListInterface;

final class ValidationErrors
{
    /**
     * @return array<string, list<string>>
     */
    public static function flatten(ConstraintViolationListInterface $violations): array
    {
        $errors = [];

        foreach ($violations as $violation) {
            $field = $violation->getPropertyPath() !== '' ? $violation->getPropertyPath() : '_';
            $errors[$field][] = (string) $violation->getMessage();
        }

        return $errors;
    }
}
