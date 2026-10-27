<?php

declare(strict_types=1);

namespace Winter\Http\Argument;

use Winter\Http\RequestContext;
use Winter\Support\Reflection;

final class ExceptionValueResolver implements ArgumentResolverInterface
{
    public function supports(\ReflectionParameter $parameter, RequestContext $context): bool
    {
        if ($context->exception === null) {
            return false;
        }

        $type = Reflection::typeName($parameter);

        return $type !== null && is_a($context->exception, $type);
    }

    public function resolve(\ReflectionParameter $parameter, RequestContext $context): mixed
    {
        return $context->exception;
    }
}
