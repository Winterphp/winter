<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Request;

test('un attribut Template rend une vue Twig avec la fonction path', function (): void {
    $response = fixtureKernel()->handle(Request::create('/hello', 'GET'));

    expect($response->getStatusCode())->toBe(200)
        ->and($response->headers->get('Content-Type'))->toContain('text/html')
        ->and((string) $response->getContent())->toContain('<h1 id="message">Hello Winter</h1>')
        ->and((string) $response->getContent())->toContain('/hello/Twig');
});

test('une page inconnue rend la page d’erreur HTML en développement', function (): void {
    $response = fixtureKernel()->handle(Request::create('/inconnue', 'GET', server: [
        'HTTP_ACCEPT' => 'text/html',
    ]));

    expect($response->getStatusCode())->toBe(404)
        ->and($response->headers->get('Content-Type'))->toContain('text/html')
        ->and((string) $response->getContent())->toContain('No route matches');
});
