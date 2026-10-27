<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Request;
use Winter\Config\Config;
use Winter\Kernel\Kernel;

function adviceKernel(): Kernel
{
    $root = \dirname(__DIR__) . '/Fixtures/App';

    return Kernel::boot($root, Config::fromRoot($root, [
        'app' => ['env' => 'test', 'debug' => true],
        'security' => ['session_storage' => 'array'],
        'logging' => ['level' => 'error'],
    ]));
}

test('un avis d’exception renvoie le statut et le corps attendus', function (): void {
    $response = adviceKernel()->handle(Request::create('/errors/teapot', server: [
        'HTTP_ACCEPT' => 'application/json',
    ]));

    expect($response->getStatusCode())->toBe(418)
        ->and(json_decode((string) $response->getContent(), true))->toBe([
            'error' => 'teapot',
            'message' => 'teapot',
            'path' => '/errors/teapot',
        ]);
});

test('#[Order] départage deux avis pour la même exception', function (): void {
    $response = adviceKernel()->handle(Request::create('/errors/boom', server: [
        'HTTP_ACCEPT' => 'application/json',
    ]));

    expect($response->getStatusCode())->toBe(502)
        ->and(json_decode((string) $response->getContent(), true)['error'])->toBe('priority');
});

test('l’avis le plus spécifique gagne sur l’ordre', function (): void {
    $response = adviceKernel()->handle(Request::create('/errors/teapot', server: [
        'HTTP_ACCEPT' => 'application/json',
    ]));

    expect($response->getStatusCode())->toBe(418);
});

test('une exception sans avis retombe sur le renderer générique', function (): void {
    $response = adviceKernel()->handle(Request::create('/errors/generic', server: [
        'HTTP_ACCEPT' => 'application/json',
    ]));

    expect($response->getStatusCode())->toBe(500)
        ->and($response->headers->get('Content-Type'))->toContain('application/json');
});
