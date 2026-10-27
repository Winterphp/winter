<?php

declare(strict_types=1);

namespace Winter\Security;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Winter\Config\Config;

final class SessionFactory
{
    public static function create(Config $config): SessionInterface
    {
        if ($config->string('security.session_storage', 'native') === 'array') {
            return new Session(new MockArraySessionStorage());
        }

        return new Session(new NativeSessionStorage([
            'name' => $config->string('security.session_name', 'winter_session') ?? 'winter_session',
            'cookie_httponly' => $config->bool('security.cookie.httponly', true),
            'cookie_samesite' => $config->string('security.cookie.samesite', 'Lax') ?? 'Lax',
            'cookie_secure' => $config->bool('security.cookie.secure', false),
        ]));
    }
}
