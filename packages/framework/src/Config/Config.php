<?php

declare(strict_types=1);

namespace Winter\Config;

final class Config
{
    public function __construct(private array $items = [])
    {
    }

    /**
     * Construit la configuration : valeurs par défaut, .env, config/*.php puis surcharges.
     *
     * @param array<string, mixed> $overrides
     */
    public static function fromRoot(string $rootDir, array $overrides = []): self
    {
        Env::load($rootDir);

        $env = (string) ($overrides['app']['env'] ?? Env::get('APP_ENV', 'dev'));
        $cacheDir = $rootDir . '/var/cache';

        $configuredProfiles = $overrides['app']['profiles'] ?? Env::get('APP_PROFILES', '');
        $configuredProfiles = \is_array($configuredProfiles)
            ? array_values(array_map('strval', $configuredProfiles))
            : self::parseProfiles((string) $configuredProfiles);
        $activeProfiles = array_values(array_unique(array_merge([$env], $configuredProfiles)));

        $defaults = [
            'app' => [
                'name' => (string) Env::get('APP_NAME', 'Winter'),
                'env' => $env,
                'profiles' => $configuredProfiles,
                'debug' => Env::bool('APP_DEBUG', $env !== 'prod'),
                'secret' => Env::get('APP_SECRET'),
                'timezone' => (string) Env::get('APP_TIMEZONE', 'UTC'),
                'namespace' => (string) Env::get('APP_NAMESPACE', 'App\\'),
                'scan_paths' => [$rootDir . '/src'],
            ],
            'database' => [
                'url' => Env::get('DATABASE_URL'),
                'paths' => [$rootDir . '/src'],
                'cache_dir' => $cacheDir . '/doctrine',
                'migrations_path' => $rootDir . '/migrations',
            ],
            'security' => [
                'mode' => (string) Env::get('AUTH_MODE', 'session'),
                'session_name' => (string) Env::get('SESSION_NAME', 'winter_session'),
                'session_storage' => (string) Env::get('SESSION_STORAGE', 'native'),
                'csrf' => Env::bool('CSRF_ENABLED', true),
                'login_path' => '/login',
                'user_class' => null,
                'identifier' => 'email',
                'cookie' => [
                    'httponly' => true,
                    'samesite' => 'Lax',
                    'secure' => Env::bool('SESSION_SECURE', $env === 'prod'),
                ],
                'headers' => [
                    'enabled' => Env::bool('SECURITY_HEADERS', true),
                    'frame_options' => 'SAMEORIGIN',
                    'hsts' => Env::bool('SECURITY_HSTS', $env === 'prod'),
                    'csp' => null,
                ],
            ],
            'jwt' => [
                'secret' => Env::get('JWT_SECRET'),
                'ttl' => (int) Env::get('JWT_TTL', 3600),
                'algo' => 'HS256',
            ],
            'twig' => [
                'paths' => [$rootDir . '/templates'],
                'cache' => $env === 'prod' ? $cacheDir . '/twig' : false,
            ],
            'logging' => [
                'level' => (string) Env::get('LOG_LEVEL', $env === 'prod' ? 'warning' : 'debug'),
                'path' => $rootDir . '/var/log/app.log',
                'stderr' => $env !== 'prod',
            ],
            'cache' => [
                'dir' => $cacheDir,
                'enabled' => $env === 'prod',
            ],
            'http' => [
                'trusted_proxies' => [],
                'base_path' => '',
            ],
        ];

        $files = (new ConfigFileLoader())->load($rootDir . '/config', $activeProfiles);
        $config = self::merge($defaults, $files);
        $merged = self::merge($config, $overrides);

        date_default_timezone_set((string) ($merged['app']['timezone'] ?? 'UTC'));

        return new self($merged);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->items;

        foreach (explode('.', $key) as $segment) {
            if (!\is_array($value) || !\array_key_exists($segment, $value)) {
                return $this->items[$key] ?? $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    public function has(string $key): bool
    {
        $sentinel = new \stdClass();
        $value = $this->get($key, $sentinel);

        return $value !== $sentinel;
    }

    public function string(string $key, ?string $default = null): ?string
    {
        $value = $this->get($key, $default);

        return $value === null ? null : (string) $value;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);

        return \is_bool($value) ? $value : \in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Profils actifs : APP_ENV puis APP_PROFILES (sans doublon).
     *
     * @return list<string>
     */
    public function profiles(): array
    {
        $env = trim($this->string('app.env', 'dev') ?? 'dev');
        $profiles = $this->get('app.profiles', []);
        $profiles = \is_array($profiles)
            ? array_map('strval', $profiles)
            : self::parseProfiles((string) $profiles);
        $all = array_merge($env !== '' ? [$env] : [], $profiles);

        return array_values(array_unique(array_filter($all, static fn (string $profile): bool => $profile !== '')));
    }

    public function hasProfile(string $profile): bool
    {
        return \in_array($profile, $this->profiles(), true);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public function with(array $overrides): self
    {
        return new self(self::merge($this->items, $overrides));
    }

    public function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $target = &$this->items;

        foreach ($segments as $segment) {
            if (!isset($target[$segment]) || !\is_array($target[$segment])) {
                $target[$segment] = [];
            }

            $target = &$target[$segment];
        }

        $target = $value;
    }

    /**
     * @return list<string>
     */
    private static function parseProfiles(string $profiles): array
    {
        $list = array_map('trim', explode(',', $profiles));

        return array_values(array_filter($list, static fn (string $profile): bool => $profile !== ''));
    }

    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    private static function merge(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (\is_array($value) && isset($base[$key]) && \is_array($base[$key])) {
                $base[$key] = self::merge($base[$key], $value);

                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }
}
