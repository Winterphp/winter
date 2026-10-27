<?php

declare(strict_types=1);

namespace Winter\Security;

interface UserProviderInterface
{
    public function loadByIdentifier(string $identifier): ?UserInterface;
}
