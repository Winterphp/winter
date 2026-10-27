<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\HttpFoundation\Request;
use Winter\Config\Config;
use Winter\Kernel\Kernel;
use Winter\Tests\Fixtures\App\Entity\Note;

function jsonKernel(): Kernel
{
    $root = \dirname(__DIR__) . '/Fixtures/App';
    $kernel = Kernel::boot($root, Config::fromRoot($root, [
        'app' => ['env' => 'test', 'debug' => true],
        'security' => ['session_storage' => 'array'],
        'database' => ['url' => 'sqlite:///:memory:'],
        'logging' => ['level' => 'error'],
    ]));

    $manager = $kernel->container()->get(EntityManagerInterface::class);
    (new SchemaTool($manager))->createSchema([$manager->getClassMetadata(Note::class)]);

    return $kernel;
}

function jsonRequest(string $method, string $path, array $body): Request
{
    return Request::create($path, $method, server: [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
    ], content: json_encode($body));
}

test('un DTO invalide renvoie 422 avec les erreurs par champ', function (): void {
    $response = jsonKernel()->handle(jsonRequest('POST', '/notes', ['title' => '']));

    expect($response->getStatusCode())->toBe(422);

    $payload = json_decode((string) $response->getContent(), true);

    expect($payload['error'])->toBe('Unprocessable Content')
        ->and($payload['details'])->toHaveKey('title');
});

test('un JSON malformé renvoie 400', function (): void {
    $request = Request::create('/notes', 'POST', server: [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
    ], content: '{"title": ');

    $response = jsonKernel()->handle($request);

    expect($response->getStatusCode())->toBe(400);
});

test('un DTO valide crée la ressource et la sérialise', function (): void {
    $kernel = jsonKernel();
    $response = $kernel->handle(jsonRequest('POST', '/notes', ['title' => 'Acheter du café']));

    expect($response->getStatusCode())->toBe(200)
        ->and(json_decode((string) $response->getContent(), true))->toMatchArray(['title' => 'Acheter du café']);

    $list = $kernel->handle(Request::create('/notes', 'GET', server: ['HTTP_ACCEPT' => 'application/json']));

    expect(json_decode((string) $list->getContent(), true))->toHaveCount(1);
});
