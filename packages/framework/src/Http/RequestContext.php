<?php

declare(strict_types=1);

namespace Winter\Http;

use Symfony\Component\HttpFoundation\Request;

final class RequestContext
{
    /**
     * @param array<string, string>  $routeVars
     * @param array{path: string, methods: list<string>, name: string, controller: string, action: string, produces: ?string}|null $route
     */
    public function __construct(
        public readonly Request $request,
        public readonly array $routeVars = [],
        public readonly ?array $route = null,
        public readonly ?\Throwable $exception = null,
    ) {
    }
}
