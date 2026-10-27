<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;

interface UserRepositoryInterface
{
    public function create(string $email, string $passwordHash, array $roles = ['ROLE_USER']): User;

    public function findByEmail(string $email): ?User;

    /**
     * @return list<User>
     */
    public function all(): array;
}
