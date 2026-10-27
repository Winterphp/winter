<?php

declare(strict_types=1);

namespace Winter\Security;

final class SecurityContext
{
    private ?UserInterface $user = null;

    public function setUser(?UserInterface $user): void
    {
        $this->user = $user;
    }

    public function user(): ?UserInterface
    {
        return $this->user;
    }

    public function isAuthenticated(): bool
    {
        return $this->user !== null;
    }

    /**
     * @return list<string>
     */
    public function roles(): array
    {
        return $this->user?->getRoles() ?? [];
    }

    /**
     * Accepte "ROLE_ADMIN" ou "ROLE_ADMIN or ROLE_EDITOR".
     */
    public function isGranted(string $expression): bool
    {
        $roles = $this->roles();

        if ($roles === []) {
            return false;
        }

        foreach (preg_split('/\s+or\s+/i', $expression) ?: [] as $role) {
            $role = trim($role);

            if ($role !== '' && \in_array($role, $roles, true)) {
                return true;
            }
        }

        return false;
    }
}
