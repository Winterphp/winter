<?php

declare(strict_types=1);

namespace Winter\Security;

use Symfony\Component\HttpFoundation\Request;

interface AuthenticatorInterface
{
    public function authenticate(Request $request): ?UserInterface;
}
