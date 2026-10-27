<?php

declare(strict_types=1);

namespace Winter\Support;

final class Str
{
    public static function snake(string $value): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $value));
    }

    public static function kebab(string $value): string
    {
        return str_replace('_', '-', self::snake($value));
    }

    public static function studly(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
    }

    /**
     * Nom de route par défaut : HomeController::index → home.index
     */
    public static function routeName(string $controllerClass, string $method): string
    {
        $short = substr((string) strrchr('\\' . $controllerClass, '\\'), 1);
        $short = (string) preg_replace('/Controller$/', '', $short);

        return self::snake($short) . '.' . $method;
    }
}
