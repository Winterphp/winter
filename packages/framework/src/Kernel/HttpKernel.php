<?php

declare(strict_types=1);

namespace Winter\Kernel;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Winter\Attribute\Template;
use Winter\Event\ApplicationEventPublisher;
use Winter\Event\KernelRequestEvent;
use Winter\Http\ControllerInvoker;
use Winter\Http\ResponseConverter;
use Winter\Http\Router;
use Winter\Security\AccessChecker;
use Winter\Security\SecurityContext;
use Winter\Support\Reflection;

final class HttpKernel
{
    private readonly MiddlewareDispatcher $dispatcher;

    /**
     * @param list<MiddlewareInterface> $middleware
     */
    public function __construct(
        private readonly Router $router,
        private readonly ControllerInvoker $invoker,
        private readonly ResponseConverter $converter,
        private readonly AccessChecker $access,
        private readonly SecurityContext $security,
        array $middleware = [],
        private readonly ?ApplicationEventPublisher $events = null,
    ) {
        $this->dispatcher = new MiddlewareDispatcher($middleware);
    }

    public function handle(Request $request): Response
    {
        return $this->dispatcher->handle($request, fn (Request $current): Response => $this->dispatch($current));
    }

    private function dispatch(Request $request): Response
    {
        $this->events?->publish(new KernelRequestEvent($request));

        $match = $this->router->match($request->getMethod(), $request->getPathInfo());
        $route = $match['route'];

        $this->access->check($route['controller'], $route['action'], $this->security);

        $result = $this->invoker->invoke($route, $request, $match['vars']);

        $method = new \ReflectionMethod($route['controller'], $route['action']);
        $template = Reflection::attribute($method, Template::class)
            ?? Reflection::attribute(new \ReflectionClass($route['controller']), Template::class);

        $name = $template instanceof Template ? $template->name : null;
        $status = $template instanceof Template ? $template->status : 200;

        return $this->converter->convert($result, $name, $status);
    }
}
