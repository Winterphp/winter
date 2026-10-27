<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Winter\Config\Config;
use Winter\Kernel\Kernel;
use Winter\Security\CsrfService;
use Winter\Security\JwtTokenService;
use Winter\Security\PasswordHasher;
use Winter\Security\SessionAuthenticator;
use Winter\Tests\Fixtures\App\Entity\Account;

function securityKernel(array $security = [], array $jwt = []): Kernel
{
    $root = \dirname(__DIR__) . '/Fixtures/App';

    $kernel = Kernel::boot($root, Config::fromRoot($root, [
        'app' => ['env' => 'test', 'debug' => true],
        'database' => ['url' => 'sqlite:///:memory:'],
        'logging' => ['level' => 'error'],
        'security' => ['session_storage' => 'array'] + $security,
        'jwt' => $jwt,
    ]));

    $manager = $kernel->container()->get(EntityManagerInterface::class);
    (new SchemaTool($manager))->createSchema([$manager->getClassMetadata(Account::class)]);

    $hasher = $kernel->container()->get(PasswordHasher::class);
    $manager->persist(new Account('admin@example.com', $hasher->hash('secret'), ['ROLE_ADMIN']));
    $manager->persist(new Account('user@example.com', $hasher->hash('secret'), ['ROLE_USER']));
    $manager->flush();

    return $kernel;
}

function apiRequest(string $path, string $method = 'GET'): Request
{
    return Request::create($path, $method, server: ['HTTP_ACCEPT' => 'application/json']);
}

test('une route #[IsGranted] renvoie 401 sans authentification', function (): void {
    $response = securityKernel()->handle(apiRequest('/admin'));

    expect($response->getStatusCode())->toBe(401)
        ->and($response->headers->get('WWW-Authenticate'))->toBe('Bearer');
});

test('une session authentifiée donne accès et 403 pour un rôle insuffisant', function (): void {
    $kernel = securityKernel();

    /** @var SessionAuthenticator $auth */
    $auth = $kernel->container()->get(SessionAuthenticator::class);

    expect($auth->attempt('admin@example.com', 'secret'))->toBeTrue()
        ->and($kernel->handle(apiRequest('/admin'))->getStatusCode())->toBe(200);

    $auth->logout();
    expect($auth->attempt('user@example.com', 'secret'))->toBeTrue()
        ->and($kernel->handle(apiRequest('/admin'))->getStatusCode())->toBe(403);

    expect($auth->attempt('admin@example.com', 'mauvais'))->toBeFalse();
});

test('un JWT valide authentifie la requête', function (): void {
    $kernel = securityKernel(['mode' => 'both'], [
        'secret' => 'une-cle-de-test-de-32-caracteres-minimum',
        'ttl' => 600,
    ]);

    /** @var JwtTokenService $tokens */
    $tokens = $kernel->container()->get(JwtTokenService::class);
    $account = new Account('admin@example.com', 'hash', ['ROLE_ADMIN']);
    $token = $tokens->create($account);

    $request = apiRequest('/admin');
    $request->headers->set('Authorization', 'Bearer ' . $token);

    expect($kernel->handle($request)->getStatusCode())->toBe(200);

    $invalid = apiRequest('/admin');
    $invalid->headers->set('Authorization', 'Bearer invalide');

    expect($kernel->handle($invalid)->getStatusCode())->toBe(401);
});

test('le middleware CSRF refuse un formulaire sans jeton et accepte le bon jeton', function (): void {
    $kernel = securityKernel();

    /** @var SessionInterface $session */
    $session = $kernel->container()->get(SessionInterface::class);
    $session->start();

    /** @var CsrfService $csrf */
    $csrf = $kernel->container()->get(CsrfService::class);
    $token = $csrf->token('form');

    $without = Request::create('/admin/form', 'POST', server: ['HTTP_ACCEPT' => 'application/json']);
    expect($kernel->handle($without)->getStatusCode())->toBe(403);

    $with = Request::create('/admin/form', 'POST', ['_token' => $token], server: [
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X-CSRF-ID' => 'form',
    ]);
    expect($kernel->handle($with)->getStatusCode())->toBe(200);
});
