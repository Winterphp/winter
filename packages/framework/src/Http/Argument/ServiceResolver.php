<?php

declare(strict_types=1);

namespace Winter\Http\Argument;

use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Winter\Container\Container;
use Winter\Container\Exception\ServiceNotFoundException;
use Winter\Http\RequestContext;
use Winter\Support\Reflection;

final class ServiceResolver implements ArgumentResolverInterface
{
    public function __construct(private readonly Container $container)
    {
    }

    public function supports(\ReflectionParameter $parameter, RequestContext $context): bool
    {
        return Reflection::typeName($parameter) !== null;
    }

    public function resolve(\ReflectionParameter $parameter, RequestContext $context): mixed
    {
        $type = (string) Reflection::typeName($parameter);

        if ($type === Request::class) {
            return $context->request;
        }

        if ($type === Container::class || $type === ContainerInterface::class) {
            return $this->container;
        }

        try {
            return $this->container->get($type);
        } catch (\Throwable $exception) {
            throw new ServiceNotFoundException(sprintf(
                'Unable to inject %s into %s::%s(). %s',
                $type,
                $parameter->getDeclaringClass()?->getName() ?? 'controller',
                $parameter->getDeclaringFunction()->getName(),
                $exception->getMessage(),
            ), previous: $exception);
        }
    }
}
