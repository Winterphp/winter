<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Request;

test('la page d’accueil rend le design Winter', function (): void {
    $response = appKernel()->handle(Request::create('/', 'GET'));

    expect($response->getStatusCode())->toBe(200)
        ->and((string) $response->getContent())->toContain('Winter')
        ->and((string) $response->getContent())->toContain('/assets/winter.css')
        ->and((string) $response->getContent())->toContain('Zéro convention imposée')
        ->and((string) $response->getContent())->toContain('Démarrer')
        ->and((string) $response->getContent())->toContain('Connexion');
});

test('la page de connexion réutilise la même feuille de style', function (): void {
    $response = appKernel()->handle(Request::create('/login', 'GET'));

    expect($response->getStatusCode())->toBe(200)
        ->and((string) $response->getContent())->toContain('/assets/winter.css')
        ->and((string) $response->getContent())->toContain('name="_token"')
        ->and((string) $response->getContent())->toContain('Se connecter');
});

test('une route inconnue renvoie 404 en HTML', function (): void {
    $response = appKernel()->handle(Request::create('/inconnue', 'GET'));

    expect($response->getStatusCode())->toBe(404)
        ->and($response->headers->get('Content-Type'))->toContain('text/html');
});
