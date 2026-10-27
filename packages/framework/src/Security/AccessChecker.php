<?php

declare(strict_types=1);

namespace Winter\Security;

use Winter\Attribute\IsGranted;
use Winter\Http\HttpException\AccessDeniedException;
use Winter\Http\HttpException\AuthenticationException;
use Winter\Support\Reflection;

final class AccessChecker
{
    public function check(string $controller, string $action, SecurityContext $context): void
    {
        $class = new \ReflectionClass($controller);
        $method = $class->getMethod($action);

        $attributes = [
            ...Reflection::attributes($class, IsGranted::class),
            ...Reflection::attributes($method, IsGranted::class),
        ];

        foreach ($attributes as $attribute) {
            if (!$attribute instanceof IsGranted) {
                continue;
            }

            if (!$context->isGranted($attribute->roles)) {
                if (!$context->isAuthenticated()) {
                    throw new AuthenticationException(sprintf(
                        'Authentication required to access %s::%s().',
                        $controller,
                        $action,
                    ));
                }

                throw new AccessDeniedException(sprintf(
                    'Access denied: %s requires "%s".',
                    $controller . '::' . $action . '()',
                    $attribute->roles,
                ));
            }
        }
    }
}
