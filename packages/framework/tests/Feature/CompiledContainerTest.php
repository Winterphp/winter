<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\HttpFoundation\Request;
use Winter\Config\Config;
use Winter\Kernel\Kernel;
use Winter\Security\JwtTokenService;
use Winter\Security\PasswordHasher;
use Winter\Tests\Fixtures\App\Entity\Account;

test('le container compilé et les routes en cache servent les requêtes en production', function (): void {
    $root = \dirname(__DIR__) . '/Fixtures/App';
    $cache = sys_get_temp_dir() . '/winter-prod-' . bin2hex(random_bytes(6));

    $config = Config::fromRoot($root, [
        'app' => ['env' => 'prod', 'debug' => false],
        'database' => ['url' => 'sqlite:///:memory:'],
        'security' => ['session_storage' => 'array', 'mode' => 'session'],
        'jwt' => ['secret' => 'une-cle-de-test-de-32-caracteres-minimum'],
        'logging' => ['level' => 'error', 'stderr' => false, 'path' => $cache . '/log/app.log'],
    ]);

    $kernel = Kernel::boot($root, $config, ['compile' => true, 'cache_dir' => $cache]);

    expect(is_file($cache . '/manifest.php'))->toBeTrue();

    $compiled = glob($cache . '/php-di/CompiledContainer_*.php') ?: [];
    expect($compiled)->not->toBeEmpty();

    $manager = $kernel->container()->get(EntityManagerInterface::class);
    (new SchemaTool($manager))->createSchema([$manager->getClassMetadata(Account::class)]);

    $hasher = $kernel->container()->get(PasswordHasher::class);
    $manager->persist(new Account('prod@example.com', $hasher->hash('secret'), ['ROLE_ADMIN']));
    $manager->flush();

    $tokens = $kernel->container()->get(JwtTokenService::class);
    $token = $tokens->create(new Account('prod@example.com', 'hash', ['ROLE_ADMIN']));

    $request = Request::create('/admin', 'GET', server: [
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
    ]);

    expect($kernel->handle($request)->getStatusCode())->toBe(401);

    // Le mode prod est session : le JWT n'est pas lu, la route reste protégée.
    $plain = $kernel->handle(Request::create('/hello/World', 'GET', server: ['HTTP_ACCEPT' => 'application/json']));

    expect($plain->getStatusCode())->toBe(200)
        ->and(json_decode((string) $plain->getContent(), true))->toBe(['message' => 'Hello Winter World'])
        ->and(is_file($cache . '/routes.php'))->toBeTrue();
});
