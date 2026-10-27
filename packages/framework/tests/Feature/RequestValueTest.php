<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Request;
use Winter\Config\Config;
use Winter\Kernel\Kernel;

function contextKernel(): Kernel
{
    $root = \dirname(__DIR__) . '/Fixtures/App';

    return Kernel::boot($root, Config::fromRoot($root, [
        'app' => ['env' => 'test', 'debug' => true],
        'security' => ['session_storage' => 'array'],
        'logging' => ['level' => 'error'],
    ]));
}

test('un en-tête est résolu avec défaut et cast', function (): void {
    $response = contextKernel()->handle(Request::create('/context/headers', server: [
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_TENANT' => 'acme',
        'HTTP_X_RETRY' => '3',
    ]));

    expect(json_decode((string) $response->getContent(), true))->toBe([
        'tenant' => 'acme',
        'retry' => 3,
        'optional' => null,
    ]);
});

test('un en-tête requis absent renvoie 400', function (): void {
    $response = contextKernel()->handle(Request::create('/context/required-header', server: [
        'HTTP_ACCEPT' => 'application/json',
    ]));

    expect($response->getStatusCode())->toBe(400);
});

test('un cookie est résolu avec défaut et cast', function (): void {
    $response = contextKernel()->handle(Request::create('/context/cookie', cookies: [
        'theme' => 'dark',
        'count' => '2',
    ], server: ['HTTP_ACCEPT' => 'application/json']));

    expect(json_decode((string) $response->getContent(), true))->toBe([
        'theme' => 'dark',
        'count' => 2,
    ]);
});

test('un cookie requis absent renvoie 400', function (): void {
    $response = contextKernel()->handle(Request::create('/context/required-cookie', server: [
        'HTTP_ACCEPT' => 'application/json',
    ]));

    expect($response->getStatusCode())->toBe(400);
});
