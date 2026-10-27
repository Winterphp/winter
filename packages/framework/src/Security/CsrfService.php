<?php

declare(strict_types=1);

namespace Winter\Security;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class CsrfService
{
    private const SESSION_KEY = '_winter_csrf';

    public function __construct(private readonly SessionInterface $session)
    {
    }

    public function token(string $id = 'default'): string
    {
        $tokens = $this->session->get(self::SESSION_KEY, []);

        if (!\is_array($tokens)) {
            $tokens = [];
        }

        if (!isset($tokens[$id]) || !\is_string($tokens[$id])) {
            $tokens[$id] = bin2hex(random_bytes(32));
            $this->session->set(self::SESSION_KEY, $tokens);
        }

        return $tokens[$id];
    }

    public function isValid(string $token, string $id = 'default'): bool
    {
        $tokens = $this->session->get(self::SESSION_KEY, []);

        if (!\is_array($tokens) || !isset($tokens[$id]) || !\is_string($tokens[$id])) {
            return false;
        }

        return hash_equals($tokens[$id], $token);
    }
}
