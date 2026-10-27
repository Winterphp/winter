<?php

declare(strict_types=1);

namespace Winter\Http;

use Symfony\Component\HttpFoundation\Request;
use Winter\Container\Container;
use Winter\Http\HttpException\NotFoundHttpException;

final class ControllerInvoker
{
    public function __construct(
        private readonly Container $container,
        private readonly ArgumentResolver $arguments,
    ) {
    }

    /**
     * @param array{path: string, methods: list<string>, name: string, controller: string, action: string, produces: ?string} $route
     * @param array<string, string>                                                                                          $vars
     */
    public function invoke(array $route, Request $request, array $vars): mixed
    {
        $controller = $this->container->get($route['controller']);

        if (!\is_object($controller)) {
            throw new \RuntimeException(sprintf('Controller "%s" is not an object.', $route['controller']));
        }

        if (!method_exists($controller, $route['action'])) {
            throw new NotFoundHttpException(sprintf(
                'Action "%s::%s()" does not exist.',
                $route['controller'],
                $route['action'],
            ));
        }

        $method = new \ReflectionMethod($controller, $route['action']);
        $arguments = $this->arguments->resolve($method, new RequestContext($request, $vars, $route));

        return $method->invokeArgs($controller, $arguments);
    }
}
