<?php

declare(strict_types=1);

namespace Winter\Http\Argument;

use Winter\Http\RequestContext;

interface ArgumentResolverInterface
{
    public function supports(\ReflectionParameter $parameter, RequestContext $context): bool;

    public function resolve(\ReflectionParameter $parameter, RequestContext $context): mixed;
}
