<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Winter\Security\CsrfService;

test('connexion, session, déconnexion', function (): void {
    $kernel = appKernel();
    createAccount($kernel, 'jane@example.com', 'motdepasse-solide');

    /** @var SessionInterface $session */
    $session = $kernel->container()->get(SessionInterface::class);
    $session->start();

    /** @var CsrfService $csrf */
    $csrf = $kernel->container()->get(CsrfService::class);
    $token = $csrf->token();

    $login = $kernel->handle(Request::create('/login', 'POST', [
        '_token' => $token,
        'email' => 'jane@example.com',
        'password' => 'motdepasse-solide',
    ]));

    expect($login->getStatusCode())->toBe(302)
        ->and($login->headers->get('Location'))->toBe('/');

    $home = $kernel->handle(Request::create('/', 'GET'));

    expect((string) $home->getContent())->toContain('Déconnexion')
        ->and((string) $home->getContent())->toContain('jane@example.com');

    $logout = $kernel->handle(Request::create('/logout', 'POST', [
        '_token' => $csrf->token(),
    ]));

    expect($logout->getStatusCode())->toBe(302)
        ->and((string) $kernel->handle(Request::create('/', 'GET'))->getContent())->toContain('Connexion');
});

test('un mauvais mot de passe réaffiche le formulaire', function (): void {
    $kernel = appKernel();
    createAccount($kernel, 'jane@example.com', 'motdepasse-solide');

    /** @var SessionInterface $session */
    $session = $kernel->container()->get(SessionInterface::class);
    $session->start();

    /** @var CsrfService $csrf */
    $csrf = $kernel->container()->get(CsrfService::class);

    $response = $kernel->handle(Request::create('/login', 'POST', [
        '_token' => $csrf->token(),
        'email' => 'jane@example.com',
        'password' => 'mauvais',
    ]));

    expect($response->getStatusCode())->toBe(200)
        ->and((string) $response->getContent())->toContain('Identifiants invalides');
});
