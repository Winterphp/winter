<?php

declare(strict_types=1);

namespace Winter\Tests\Fixtures\App\Formatter;

use Winter\Attribute\Component;

#[Component]
final class SimpleGreetingFormatter implements GreetingFormatter
{
    public function format(string $name): string
    {
        return 'Hello ' . $name;
    }
}
