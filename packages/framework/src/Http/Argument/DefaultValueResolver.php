<?php

declare(strict_types=1);

namespace Winter\Http\Argument;

use Winter\Http\HttpException\BadRequestHttpException;
use Winter\Http\RequestContext;

final class DefaultValueResolver implements ArgumentResolverInterface
{
    public function supports(\ReflectionParameter $parameter, RequestContext $context): bool
    {
        return true;
    }

    public function resolve(\ReflectionParameter $parameter, RequestContext $context): mixed
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->allowsNull()) {
            return null;
        }

        throw new BadRequestHttpException(sprintf(
            'Cannot resolve parameter $%s of %s::%s(). Add #[PathVariable], #[RequestParam], #[RequestBody], #[CurrentUser] or a service type-hint.',
            $parameter->getName(),
            $parameter->getDeclaringClass()?->getName() ?? 'controller',
            $parameter->getDeclaringFunction()->getName(),
        ));
    }
}
