<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateUserRequest
{
    #[Assert\NotBlank]
    #[Assert\Email(message: 'Adresse e-mail invalide.')]
    public string $email = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 72, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.')]
    public string $password = '';
}
