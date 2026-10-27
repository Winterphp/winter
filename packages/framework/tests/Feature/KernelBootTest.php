<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Request;
use Winter\Config\Config;
use Winter\Kernel\Kernel;

function fixtureKernel(): Kernel
{
    $root = \dirname(__DIR__) . '/Fixtures/App';

    return Kernel::boot($root, Config::fromRoot($root, [
        'app' => ['env' => 'test', 'debug' => true],
        'security' => ['session_storage' => 'array'],
        'logging' => ['level' => 'error'],
    ]));
}

test('une route par attributs répond en JSON', function (): void {
    $response = fixtureKernel()->handle(Request::create('/hello/World'));

    expect($response->getStatusCode())->toBe(200)
        ->and($response->headers->get('Content-Type'))->toContain('application/json')
        ->and(json_decode((string) $response->getContent(), true))->toBe(['message' => 'Hello Winter World']);
});

test('une route inconnue renvoie 404 en JSON pour les clients API', function (): void {
    $response = fixtureKernel()->handle(Request::create('/inconnue', 'GET', server: [
        'HTTP_ACCEPT' => 'application/json',
    ]));

    expect($response->getStatusCode())->toBe(404)
        ->and(json_decode((string) $response->getContent(), true))->toHaveKey('error');
});

test('une méthode non autorisée renvoie 405 avec Allow', function (): void {
    $response = fixtureKernel()->handle(Request::create('/hello/World', 'POST', server: [
        'HTTP_ACCEPT' => 'application/json',
        'CONTENT_TYPE' => 'application/json',
    ]));

    expect($response->getStatusCode())->toBe(405)
        ->and($response->headers->get('Allow'))->toContain('GET');
});

test('le cache du manifeste est réutilisé entre deux boots', function (): void {
    $kernel = fixtureKernel();
    $manifestFile = $kernel->paths()->manifestFile();

    expect(is_file($manifestFile))->toBeTrue();

    $before = filemtime($manifestFile);
    sleep(1);
    fixtureKernel();

    expect(filemtime($manifestFile))->toBe($before);
});
