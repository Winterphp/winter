<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Winter\Attribute\SecurityUser;
use Winter\Security\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[SecurityUser(identifier: 'email')]
final class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    private string $password;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    /**
     * @param list<string> $roles
     */
    public function __construct(string $email, string $passwordHash, array $roles = ['ROLE_USER'])
    {
        $this->email = $email;
        $this->password = $passwordHash;
        $this->roles = $roles;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getPasswordHash(): ?string
    {
        return $this->password;
    }
}
