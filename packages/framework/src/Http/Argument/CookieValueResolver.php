<?php

declare(strict_types=1);

namespace Winter\Http\Argument;

use Winter\Attribute\CookieValue;
use Winter\Http\HttpException\BadRequestHttpException;
use Winter\Http\RequestContext;
use Winter\Support\Reflection;

final class CookieValueResolver implements ArgumentResolverInterface
{
    public function supports(\ReflectionParameter $parameter, RequestContext $context): bool
    {
        return Reflection::attribute($parameter, CookieValue::class) !== null;
    }

    public function resolve(\ReflectionParameter $parameter, RequestContext $context): mixed
    {
        /** @var CookieValue $attribute */
        $attribute = Reflection::attribute($parameter, CookieValue::class);
        $value = $context->request->cookies->get($attribute->name);

        if ($value === null) {
            if ($attribute->hasDefault) {
                return $attribute->default;
            }

            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            throw new BadRequestHttpException(sprintf('Missing cookie "%s".', $attribute->name));
        }

        return RequestHeaderResolver::cast($parameter, $value);
    }
}
