<?php

declare(strict_types=1);

namespace Winter\Http\Argument;

use Winter\Attribute\CurrentUser;
use Winter\Http\RequestContext;
use Winter\Security\SecurityContext;
use Winter\Support\Reflection;

final class CurrentUserResolver implements ArgumentResolverInterface
{
    public function __construct(private readonly SecurityContext $context)
    {
    }

    public function supports(\ReflectionParameter $parameter, RequestContext $context): bool
    {
        return Reflection::attribute($parameter, CurrentUser::class) !== null;
    }

    public function resolve(\ReflectionParameter $parameter, RequestContext $context): mixed
    {
        return $this->context->user();
    }
}
