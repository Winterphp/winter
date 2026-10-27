<?php

declare(strict_types=1);

namespace Winter\Http;

final class RouteGenerator
{
    /** @var array<string, string> */
    private array $paths = [];

    /**
     * @param list<array{path: string, methods: list<string>, name: string, controller: string, action: string, produces: ?string}> $routes
     */
    public function __construct(array $routes)
    {
        foreach ($routes as $route) {
            $this->paths[$route['name']] = $route['path'];
        }
    }

    /**
     * @param array<string, scalar> $parameters
     */
    public function path(string $name, array $parameters = []): string
    {
        if (!isset($this->paths[$name])) {
            throw new \InvalidArgumentException(sprintf('Unknown route "%s".', $name));
        }

        $path = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', static function (array $matches) use ($parameters): string {
            $key = $matches[1];

            if (!\array_key_exists($key, $parameters)) {
                throw new \InvalidArgumentException(sprintf('Missing route parameter "%s".', $key));
            }

            return rawurlencode((string) $parameters[$key]);
        }, $this->paths[$name]);

        return (string) $path;
    }

    public function has(string $name): bool
    {
        return isset($this->paths[$name]);
    }
}
