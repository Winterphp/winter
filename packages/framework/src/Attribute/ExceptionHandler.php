<?php

declare(strict_types=1);

namespace Winter\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
final class ExceptionHandler
{
    /**
     * @param class-string<\Throwable> $exception
     */
    public function __construct(public readonly string $exception)
    {
    }
}
