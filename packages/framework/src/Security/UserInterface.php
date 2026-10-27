<?php

declare(strict_types=1);

namespace Winter\Security;

interface UserInterface
{
    public function getIdentifier(): string;

    /**
     * @return list<string>
     */
    public function getRoles(): array;

    public function getPasswordHash(): ?string;
}
