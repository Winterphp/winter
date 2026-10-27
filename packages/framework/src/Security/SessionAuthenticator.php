<?php

declare(strict_types=1);

namespace Winter\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class SessionAuthenticator implements AuthenticatorInterface
{
    private const KEY = '_winter_user_id';

    public function __construct(
        private readonly SessionInterface $session,
        private readonly UserProviderInterface $provider,
        private readonly PasswordHasher $hasher,
    ) {
    }

    public function attempt(string $identifier, string $password): bool
    {
        $user = $this->provider->loadByIdentifier($identifier);

        if ($user === null || $user->getPasswordHash() === null) {
            return false;
        }

        if (!$this->hasher->verify($password, $user->getPasswordHash())) {
            return false;
        }

        $this->login($user);

        return true;
    }

    public function login(UserInterface $user): void
    {
        if (!$this->session->isStarted()) {
            $this->session->start();
        }

        try {
            $this->session->migrate(true);
        } catch (\Throwable) {
            // Certains stockages de session ne supportent pas la régénération.
        }

        $this->session->set(self::KEY, $user->getIdentifier());
    }

    public function logout(): void
    {
        $this->session->remove(self::KEY);

        try {
            $this->session->invalidate();
        } catch (\Throwable) {
            // idem
        }
    }

    public function authenticate(Request $request): ?UserInterface
    {
        $identifier = $this->session->get(self::KEY);

        if (!\is_string($identifier) || $identifier === '') {
            return null;
        }

        return $this->provider->loadByIdentifier($identifier);
    }
}
