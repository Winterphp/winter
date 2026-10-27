<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Request;

test('/docs redirige vers la page de présentation', function (): void {
    $response = appKernel()->handle(Request::create('/docs', 'GET'));

    expect($response->getStatusCode())->toBe(302)
        ->and($response->headers->get('Location'))->toBe('/docs/introduction');
});

test('la présentation publie le README dans le design Winter', function (): void {
    $response = appKernel()->handle(Request::create('/docs/introduction', 'GET'));
    $html = (string) $response->getContent();

    expect($response->getStatusCode())->toBe(200)
        ->and($html)->toContain('/assets/winter.css')
        ->and($html)->toContain('class="doc-nav"')
        ->and($html)->toContain('aria-current="page"')
        ->and($html)->toContain('href="/docs/architecture"')
        ->and($html)->toContain('Winter');
});

test('la référence des attributs rend les ancres, le sommaire et les blocs de code', function (): void {
    $response = appKernel()->handle(Request::create('/docs/attributs', 'GET'));
    $html = (string) $response->getContent();

    expect($response->getStatusCode())->toBe(200)
        ->and($html)->toContain('<code>#[Component(?string $name = null)]</code>')
        ->and($html)->toContain('id="stereotypes"')
        ->and($html)->toContain('href="#stereotypes"')
        ->and($html)->toContain('<div class="doc-code"><pre><code>')
        ->and($html)->toContain('class="doc-pager-link"');
});

test('l’architecture rend le comparatif Spring Boot en tableau', function (): void {
    $response = appKernel()->handle(Request::create('/docs/architecture', 'GET'));
    $html = (string) $response->getContent();

    expect($response->getStatusCode())->toBe(200)
        ->and($html)->toContain('<div class="doc-table">')
        ->and($html)->toContain('<th>Spring Boot</th>')
        ->and($html)->toContain('<td>singleton par requête</td>');
});

test('les blocs php et dotenv de la référence des attributs sont colorisés', function (): void {
    $html = (string) appKernel()->handle(Request::create('/docs/attributs', 'GET'))->getContent();

    expect($html)->toContain('<span class="tok-attr">#[</span><span class="tok-attr">Component</span>')
        ->and($html)->toContain('<span class="tok-key">final</span> <span class="tok-key">class</span> <span class="tok-type">SmtpMailer</span>')
        ->and($html)->toContain('<span class="tok-key">APP_ENV</span>=<span class="tok-str">staging</span>')
        ->and($html)->toContain('<span class="tok-key">config</span>/');
});

test('l’arborescence du cycle de requête est colorisée, sans coloration PHP', function (): void {
    $html = (string) appKernel()->handle(Request::create('/docs/architecture', 'GET'))->getContent();

    expect($html)->toContain('<span class="tok-fn">boot</span>')
        ->and($html)->toContain('<span class="tok-type">ContainerBuilder</span>')
        ->and($html)->toContain('<span class="tok-dim">└── </span>')
        ->and($html)->not->toContain('tok-attr');
});

test('les commandes bash et la structure d’application sont colorisées', function (): void {
    $html = (string) appKernel()->handle(Request::create('/docs/introduction', 'GET'))->getContent();

    expect($html)->toContain('<span class="tok-fn">composer</span> <span class="tok-key">install</span>')
        ->and($html)->toContain('<span class="tok-attr">--testsuite=Framework</span>')
        ->and($html)->toContain('<span class="tok-dim"># Pest : framework + squelette</span>')
        ->and($html)->toContain('<span class="tok-type">index.php</span>');
});

test('chaque page du sommaire répond et affiche son titre', function (): void {
    $kernel = appKernel();

    foreach (['introduction', 'architecture', 'attributs', 'migration-depuis-laravel', 'audit-securite'] as $slug) {
        $response = $kernel->handle(Request::create('/docs/' . $slug, 'GET'));

        expect($response->getStatusCode())->toBe(200)
            ->and((string) $response->getContent())->toContain('Documentation');
    }
});

test('une page de documentation inconnue renvoie 404', function (): void {
    $response = appKernel()->handle(Request::create('/docs/inconnu', 'GET'));

    expect($response->getStatusCode())->toBe(404);
});

test('un chemin de traversée ne donne accès à aucun fichier', function (): void {
    $response = appKernel()->handle(Request::create('/docs/..%2F..%2Fetc%2Fpasswd', 'GET'));

    expect($response->getStatusCode())->toBe(404);
});

test('l’en-tête du site expose le lien Documentation', function (): void {
    $response = appKernel()->handle(Request::create('/', 'GET'));

    expect($response->getStatusCode())->toBe(200)
        ->and((string) $response->getContent())->toContain('<a class="nav-link" href="/docs">Documentation</a>');
});
