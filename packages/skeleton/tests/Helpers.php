<?php

declare(strict_types=1);

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\HttpFoundation\Request;
use Winter\Config\Config;
use Winter\Kernel\Kernel;
use Winter\Security\PasswordHasher;

/**
 * @param array<string, mixed> $overrides
 */
function appKernel(array $overrides = []): Kernel
{
    $root = \dirname(__DIR__);

    $config = Config::fromRoot($root, array_replace_recursive([
        'app' => ['env' => 'test', 'debug' => true],
        'database' => ['url' => 'sqlite:///:memory:'],
        'security' => ['session_storage' => 'array'],
        'logging' => ['level' => 'error'],
    ], $overrides));

    $kernel = Kernel::boot($root, $config);

    $manager = $kernel->container()->get(EntityManagerInterface::class);
    (new SchemaTool($manager))->createSchema([$manager->getClassMetadata(User::class)]);

    return $kernel;
}

/**
 * @param array<string, mixed> $payload
 */
function jsonRequestTo(string $method, string $path, array $payload = []): Request
{
    return Request::create($path, $method, server: [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
    ], content: $payload === [] ? null : json_encode($payload));
}

function createAccount(Kernel $kernel, string $email, string $password, array $roles = ['ROLE_USER']): User
{
    $manager = $kernel->container()->get(EntityManagerInterface::class);
    $hasher = $kernel->container()->get(PasswordHasher::class);
    $user = new User($email, $hasher->hash($password), $roles);
    $manager->persist($user);
    $manager->flush();

    return $user;
}
