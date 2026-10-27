<?php

declare(strict_types=1);

namespace Winter\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Route
{
    /** @var list<string> */
    public readonly array $methods;

    /**
     * @param list<string>|string $methods
     */
    public function __construct(
        public readonly string $path,
        array|string $methods = ['GET'],
        public readonly ?string $name = null,
        public readonly ?string $produces = null,
    ) {
        $methods = \is_string($methods) ? [$methods] : $methods;
        $this->methods = array_values(array_unique(array_map('strtoupper', $methods)));
    }
}
