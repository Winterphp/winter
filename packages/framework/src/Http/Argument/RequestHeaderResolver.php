<?php

declare(strict_types=1);

namespace Winter\Http\Argument;

use Winter\Attribute\RequestHeader;
use Winter\Http\HttpException\BadRequestHttpException;
use Winter\Http\RequestContext;
use Winter\Support\Reflection;

final class RequestHeaderResolver implements ArgumentResolverInterface
{
    public function supports(\ReflectionParameter $parameter, RequestContext $context): bool
    {
        return Reflection::attribute($parameter, RequestHeader::class) !== null;
    }

    public function resolve(\ReflectionParameter $parameter, RequestContext $context): mixed
    {
        /** @var RequestHeader $attribute */
        $attribute = Reflection::attribute($parameter, RequestHeader::class);
        $value = $context->request->headers->get($attribute->name);

        if ($value === null) {
            if ($attribute->hasDefault) {
                return $attribute->default;
            }

            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            throw new BadRequestHttpException(sprintf('Missing request header "%s".', $attribute->name));
        }

        return self::cast($parameter, $value);
    }

    public static function cast(\ReflectionParameter $parameter, mixed $value): mixed
    {
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
