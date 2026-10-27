<?php

declare(strict_types=1);

namespace Winter\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class PostMapping extends Route
{
    public function __construct(string $path, ?string $name = null, ?string $produces = null)
    {
        parent::__construct($path, ['POST'], $name, $produces);
    }
}
