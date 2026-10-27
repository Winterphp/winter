<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Request;
use Winter\Config\Config;
use Winter\Kernel\Kernel;

/**
 * @param array<string, mixed> $headers
 */
function headersKernel(array $headers = []): Kernel
{
    $root = \dirname(__DIR__) . '/Fixtures/App';

    return Kernel::boot($root, Config::fromRoot($root, [
        'app' => ['env' => 'test', 'debug' => true],
        'security' => ['session_storage' => 'array', 'headers' => $headers],
        'logging' => ['level' => 'error'],
    ]));
}

test('les en-têtes de sécurité sont ajoutés aux réponses et aux erreurs', function (): void {
    $kernel = headersKernel();

    $ok = $kernel->handle(Request::create('/hello/World', server: ['HTTP_ACCEPT' => 'application/json']));
    $notFound = $kernel->handle(Request::create('/inconnue', server: ['HTTP_ACCEPT' => 'application/json']));

    foreach ([$ok, $notFound] as $response) {
        expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
            ->and($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN')
            ->and($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin');
    }
});

test('HSTS est émis uniquement en HTTPS et si activé', function (): void {
    $kernel = headersKernel(['hsts' => true]);

    $http = $kernel->handle(Request::create('/hello/World', server: [
        'HTTP_ACCEPT' => 'application/json',
    ]));

    $https = $kernel->handle(Request::create('https://example.test/hello/World', server: [
        'HTTPS' => 'on',
        'HTTP_ACCEPT' => 'application/json',
    ]));

    expect($http->headers->has('Strict-Transport-Security'))->toBeFalse()
        ->and($https->headers->get('Strict-Transport-Security'))->toContain('max-age=31536000');
});

test('une CSP configurée est ajoutée', function (): void {
    $response = headersKernel(['csp' => "default-src 'self'"])
        ->handle(Request::create('/hello/World', server: ['HTTP_ACCEPT' => 'application/json']));

    expect($response->headers->get('Content-Security-Policy'))->toBe("default-src 'self'");
});
