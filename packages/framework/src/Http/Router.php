<?php

declare(strict_types=1);

namespace Winter\Http;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Winter\Container\Manifest;
use Winter\Http\HttpException\MethodNotAllowedHttpException;
use Winter\Http\HttpException\NotFoundHttpException;

use function FastRoute\cachedDispatcher;
use function FastRoute\simpleDispatcher;

final class Router
{
    /** @var Dispatcher|null */
    private ?Dispatcher $dispatcher = null;

    public function __construct(
        private readonly Manifest $manifest,
        private readonly bool $debug = false,
        private readonly ?string $cacheFile = null,
    ) {
    }

    /**
     * @return array{route: array{path: string, methods: list<string>, name: string, controller: string, action: string, produces: ?string}, vars: array<string, string>}
     */
    public function match(string $method, string $path): array
    {
        $path = '/' . ltrim($path, '/');
        $info = $this->dispatcher()->dispatch($method, $path);

        if ($info[0] === Dispatcher::NOT_FOUND) {
            throw new NotFoundHttpException(sprintf('No route matches "%s %s".', $method, $path));
        }

        if ($info[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            /** @var list<string> $allowed */
            $allowed = $info[1];

            throw new MethodNotAllowedHttpException($allowed);
        }

        /** @var int $handler */
        $handler = $info[1];
        $route = $this->manifest->routes[$handler]
            ?? throw new NotFoundHttpException('Route definition is missing from the manifest. Run cache:compile.');

        /** @var array<string, string> $vars */
        $vars = $info[2] ?? [];

        return ['route' => $route, 'vars' => $vars];
    }

    /**
     * @return list<array{method: string, path: string, name: string, controller: string, action: string}>
     */
    public function list(): array
    {
        $list = [];

        foreach ($this->manifest->routes as $route) {
            foreach ($route['methods'] as $method) {
                $list[] = [
                    'method' => $method,
                    'path' => $route['path'],
                    'name' => $route['name'],
                    'controller' => $route['controller'],
                    'action' => $route['action'],
                ];
            }
        }

        return $list;
    }

    private function dispatcher(): Dispatcher
    {
        if ($this->dispatcher !== null) {
            return $this->dispatcher;
        }

        $definition = function (RouteCollector $collector): void {
            foreach ($this->manifest->routes as $index => $route) {
                $collector->addRoute($route['methods'], $route['path'], $index);
            }
        };

        if (!$this->debug && $this->cacheFile !== null) {
            $directory = \dirname($this->cacheFile);

            if (!is_dir($directory)) {
                @mkdir($directory, 0o775, true);
            }

            $this->dispatcher = cachedDispatcher($definition, [
                'cacheFile' => $this->cacheFile,
                'cacheDisabled' => false,
            ]);
        } else {
            $this->dispatcher = simpleDispatcher($definition);
        }

        return $this->dispatcher;
    }
}
