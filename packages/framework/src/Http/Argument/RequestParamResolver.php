<?php

declare(strict_types=1);

namespace Winter\Http\Argument;

use Winter\Attribute\RequestParam;
use Winter\Http\HttpException\BadRequestHttpException;
use Winter\Http\RequestContext;
use Winter\Support\Reflection;

final class RequestParamResolver implements ArgumentResolverInterface
{
    public function supports(\ReflectionParameter $parameter, RequestContext $context): bool
    {
        return Reflection::attribute($parameter, RequestParam::class) !== null;
    }

    public function resolve(\ReflectionParameter $parameter, RequestContext $context): mixed
    {
        /** @var RequestParam $attribute */
        $attribute = Reflection::attribute($parameter, RequestParam::class);
        $value = $context->request->query->all()[$attribute->name] ?? null;

        if ($value === null) {
            if ($attribute->hasDefault) {
                return $attribute->default;
            }

            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            throw new BadRequestHttpException(sprintf('Missing query parameter "%s".', $attribute->name));
        }

        $type = $parameter->getType();

        if (!$type instanceof \ReflectionNamedType) {
            return $value;
        }

        return match ($type->getName()) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => \in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true),
            'array' => \is_array($value) ? $value : [$value],
            default => $value,
        };
    }
}
