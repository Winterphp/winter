<?php

declare(strict_types=1);

namespace Winter\Config;

use Dotenv\Dotenv;

final class Env
{
    public static function load(string $rootDir): void
    {
        if (!is_file($rootDir . '/.env')) {
            return;
        }

        Dotenv::createImmutable($rootDir)->safeLoad();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);

        if ($value === null) {
            return $default;
        }

        if (\is_bool($value)) {
            return $value;
        }

        return \in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }
}
