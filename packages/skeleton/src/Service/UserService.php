<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepositoryInterface;
use Winter\Attribute\Service;
use Winter\Http\HttpException\BadRequestHttpException;
use Winter\Security\PasswordHasher;

#[Service]
final class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly PasswordHasher $hasher,
    ) {
    }

    public function register(string $email, string $plainPassword): User
    {
        if ($this->users->findByEmail($email) !== null) {
            throw new BadRequestHttpException(sprintf('Un compte existe déjà pour %s.', $email));
        }

        return $this->users->create($email, $this->hasher->hash($plainPassword));
    }

    /**
     * @return list<User>
     */
    public function all(): array
    {
        return $this->users->all();
    }
}
