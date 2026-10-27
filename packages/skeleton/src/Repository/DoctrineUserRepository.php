<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Winter\Attribute\Repository;
use Winter\Data\Repository as BaseRepository;

#[Repository]
final class DoctrineUserRepository extends BaseRepository implements UserRepositoryInterface
{
    protected function entityClass(): string
    {
        return User::class;
    }

    public function create(string $email, string $passwordHash, array $roles = ['ROLE_USER']): User
    {
        $user = new User($email, $passwordHash, $roles);
        $this->save($user);

        return $user;
    }

    public function findByEmail(string $email): ?User
    {
        /** @var User|null $user */
        $user = $this->findOneBy(['email' => $email]);

        return $user;
    }

    public function all(): array
    {
        return $this->findAll(['id' => 'ASC']);
    }
}
