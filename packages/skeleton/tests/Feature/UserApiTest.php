<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Request;
use Winter\Security\SessionAuthenticator;

test('une création invalide renvoie 422 avec les champs en erreur', function (): void {
    $response = appKernel()->handle(jsonRequestTo('POST', '/api/users', [
        'email' => 'pas-un-email',
        'password' => 'court',
    ]));

    expect($response->getStatusCode())->toBe(422);

    $payload = json_decode((string) $response->getContent(), true);

    expect($payload['details'])->toHaveKeys(['email', 'password']);
});

test('créer un utilisateur puis lister en administrateur', function (): void {
    $kernel = appKernel();

    $create = $kernel->handle(jsonRequestTo('POST', '/api/users', [
        'email' => 'jane@example.com',
        'password' => 'motdepasse-solide',
    ]));

    expect($create->getStatusCode())->toBe(200)
        ->and(json_decode((string) $create->getContent(), true))->toMatchArray([
            'email' => 'jane@example.com',
            'roles' => ['ROLE_USER'],
        ]);

    expect($kernel->handle(jsonRequestTo('GET', '/api/users'))->getStatusCode())->toBe(401);

    createAccount($kernel, 'admin@example.com', 'motdepasse-solide', ['ROLE_ADMIN']);

    /** @var SessionAuthenticator $auth */
    $auth = $kernel->container()->get(SessionAuthenticator::class);
    expect($auth->attempt('admin@example.com', 'motdepasse-solide'))->toBeTrue();

    $list = $kernel->handle(Request::create('/api/users', 'GET', server: ['HTTP_ACCEPT' => 'application/json']));

    expect($list->getStatusCode())->toBe(200)
        ->and(json_decode((string) $list->getContent(), true))->toHaveCount(2);
});

test('un doublon renvoie 400', function (): void {
    $kernel = appKernel();
    createAccount($kernel, 'jane@example.com', 'motdepasse-solide');

    $response = $kernel->handle(jsonRequestTo('POST', '/api/users', [
        'email' => 'jane@example.com',
        'password' => 'motdepasse-solide',
    ]));

    expect($response->getStatusCode())->toBe(400);
});
