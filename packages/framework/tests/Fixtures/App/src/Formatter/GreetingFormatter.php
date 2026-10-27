<?php

declare(strict_types=1);

namespace Winter\Tests\Fixtures\App\Formatter;

interface GreetingFormatter
{
    public function format(string $name): string;
}
