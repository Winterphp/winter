<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\CreateUserRequest;
use App\Service\UserService;
use Winter\Attribute\Controller;
use Winter\Attribute\GetMapping;
use Winter\Attribute\IsGranted;
use Winter\Attribute\PostMapping;
use Winter\Attribute\RequestBody;
use Winter\Attribute\Route;

#[Controller]
#[Route('/api/users')]
final class UserController
{
    public function __construct(private readonly UserService $users)
    {
    }

    #[GetMapping('')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(): array
    {
        return array_map(static fn ($user): array => [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ], $this->users->all());
    }

    #[PostMapping('')]
    public function create(#[RequestBody] CreateUserRequest $request): array
    {
        $user = $this->users->register($request->email, $request->password);

        return ['id' => $user->getId(), 'email' => $user->getEmail(), 'roles' => $user->getRoles()];
    }
}
