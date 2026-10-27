<?php

declare(strict_types=1);

namespace Winter\Security;

final class NullUserProvider implements UserProviderInterface
{
    public function loadByIdentifier(string $identifier): ?UserInterface
    {
        return null;
    }
}
