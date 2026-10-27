<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Winter\Attribute\Controller;
use Winter\Attribute\GetMapping;
use Winter\Attribute\PostMapping;
use Winter\Attribute\Template;
use Winter\Security\SessionAuthenticator;

#[Controller]
final class SecurityController
{
    public function __construct(private readonly SessionAuthenticator $authenticator)
    {
    }

    #[GetMapping('/login')]
    #[Template('security/login.html.twig')]
    public function form(): array
    {
        return ['error' => null, 'email' => ''];
    }

    #[PostMapping('/login')]
    #[Template('security/login.html.twig')]
    public function login(Request $request): Response|array
    {
        $email = (string) $request->request->get('email', '');
        $password = (string) $request->request->get('password', '');

        if ($this->authenticator->attempt($email, $password)) {
            return new RedirectResponse('/');
        }

        return ['error' => 'Identifiants invalides.', 'email' => $email];
    }

    #[PostMapping('/logout')]
    public function logout(): Response
    {
        $this->authenticator->logout();

        return new RedirectResponse('/login');
    }
}
