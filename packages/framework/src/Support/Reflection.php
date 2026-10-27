<?php

declare(strict_types=1);

namespace Winter\Support;

use ReflectionAttribute;
use ReflectionNamedType;
use ReflectionParameter;

final class Reflection
{
    /**
     * Retourne la première instance d'attribut correspondant à $class (sous-classes incluses).
     */
    public static function attribute(object $reflector, string $class): ?object
    {
        $attributes = self::attributes($reflector, $class);

        return $attributes[0] ?? null;
    }

    /**
     * @return list<object>
     */
    public static function attributes(object $reflector, string $class): array
    {
        if (!method_exists($reflector, 'getAttributes')) {
            return [];
        }

        /** @var list<ReflectionAttribute> $attributes */
        $attributes = $reflector->getAttributes($class, ReflectionAttribute::IS_INSTANCEOF);

        return array_map(static fn (ReflectionAttribute $a): object => $a->newInstance(), $attributes);
    }

    /**
     * Nom de classe d'un paramètre typé, ou null si scalaire / absent / union.
     */
    public static function typeName(ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        return $type->getName();
    }
}
