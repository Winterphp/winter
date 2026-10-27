<?php

declare(strict_types=1);

namespace Winter\Http\Argument;

use Winter\Attribute\PathVariable;
use Winter\Http\HttpException\BadRequestHttpException;
use Winter\Http\RequestContext;
use Winter\Support\Reflection;

final class PathVariableResolver implements ArgumentResolverInterface
{
    public function supports(\ReflectionParameter $parameter, RequestContext $context): bool
    {
        if (Reflection::attribute($parameter, PathVariable::class) !== null) {
            return true;
        }

        return \array_key_exists($parameter->getName(), $context->routeVars);
    }

    public function resolve(\ReflectionParameter $parameter, RequestContext $context): mixed
    {
        $attribute = Reflection::attribute($parameter, PathVariable::class);
        $name = $attribute instanceof PathVariable ? ($attribute->name ?? $parameter->getName()) : $parameter->getName();

        if (!\array_key_exists($name, $context->routeVars)) {
            throw new BadRequestHttpException(sprintf('Missing route variable "%s".', $name));
        }

        return $this->cast($context->routeVars[$name], $parameter);
    }

    private function cast(string $value, \ReflectionParameter $parameter): int|float|bool|string
    {
        $type = $parameter->getType();

        if ($type instanceof \ReflectionNamedType) {
            return match ($type->getName()) {
                'int' => (int) $value,
                'float' => (float) $value,
                'bool' => \in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true),
                default => $value,
            };
        }

        return $value;
    }
}
