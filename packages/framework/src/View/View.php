<?php

declare(strict_types=1);

namespace Winter\View;

final class View
{
    /**
     * @param array<string, mixed> $context
     * @param array<string, string> $headers
     */
    public function __construct(
        public readonly string $template,
        public readonly array $context = [],
        public readonly int $status = 200,
        public readonly array $headers = [],
    ) {
    }
}
