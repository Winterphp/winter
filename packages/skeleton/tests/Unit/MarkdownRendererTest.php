<?php

declare(strict_types=1);

use App\Documentation\CodeHighlighter;
use App\Documentation\MarkdownRenderer;

function markdownRenderer(): MarkdownRenderer
{
    return new MarkdownRenderer(new CodeHighlighter());
}

test('les titres de niveau 2 et 3 alimentent le sommaire avec des ancres stables', function (): void {
    $rendered = markdownRenderer()->render("## Compilation : pourquoi\n\n### `#[Value]` et accents (é)\n");

    expect($rendered['html'])->toContain('<h2 id="compilation-pourquoi">Compilation : pourquoi</h2>')
        ->and($rendered['html'])->toContain('<h3 id="value-et-accents-e"><code>#[Value]</code> et accents (é)</h3>')
        ->and($rendered['toc'])->toBe([
            ['level' => 2, 'id' => 'compilation-pourquoi', 'title' => 'Compilation : pourquoi'],
            ['level' => 3, 'id' => 'value-et-accents-e', 'title' => '<code>#[Value]</code> et accents (é)'],
        ]);
});

test('les ancres dupliquées sont dédoublonnées', function (): void {
    $rendered = markdownRenderer()->render("## Profils\n\n## Profils\n");

    expect($rendered['toc'][0]['id'])->toBe('profils')
        ->and($rendered['toc'][1]['id'])->toBe('profils-2');
});

test('le titre de niveau 1 reste au gabarit', function (): void {
    $rendered = markdownRenderer()->render("# Architecture\n\n## Section\n");

    expect($rendered['html'])->not->toContain('<h1')
        ->and($rendered['html'])->not->toContain('# Architecture');
});

test('un bloc tagué php est colorisé sans laisser passer de HTML', function (): void {
    $rendered = markdownRenderer()->render("```php\necho '<script>alert(1)</script>';\n```\n");

    expect($rendered['html'])->toContain('<div class="doc-code"><pre><code>')
        ->and($rendered['html'])->toContain('<span class="tok-key">echo</span>')
        ->and($rendered['html'])->toContain('<span class="tok-str">&#039;&lt;script&gt;alert(1)&lt;/script&gt;&#039;</span>')
        ->and($rendered['html'])->not->toContain('<script>');
});

test('un bloc sans langage reste du texte échappé, sans coloration', function (): void {
    $rendered = markdownRenderer()->render("```\n<?php echo '<script>';\n```\n");

    expect($rendered['html'])->toContain("<div class=\"doc-code\"><pre><code>&lt;?php echo &#039;&lt;script&gt;&#039;;</code></pre></div>")
        ->and($rendered['html'])->not->toContain('tok-');
});

test('les tableaux deviennent des tableaux HTML', function (): void {
    $rendered = markdownRenderer()->render(
        "| Spring Boot | Winter v1 |\n|---|---|\n| Beans partagés | singleton par requête |\n",
    );

    expect($rendered['html'])->toContain('<div class="doc-table"><table><thead><tr>')
        ->and($rendered['html'])->toContain('<th>Spring Boot</th><th>Winter v1</th>')
        ->and($rendered['html'])->toContain('<td>Beans partagés</td><td>singleton par requête</td>');
});

test('les listes et la mise en forme en ligne sont converties', function (): void {
    $rendered = markdownRenderer()->render("- `#[Values]` **gras**\n- second\n\n1. un\n2. deux\n");

    expect($rendered['html'])->toContain('<ul><li><code>#[Values]</code> <strong>gras</strong></li><li>second</li></ul>')
        ->and($rendered['html'])->toContain('<ol><li>un</li><li>deux</li></ol>');
});

test('les liens Markdown pointent vers les pages du site', function (): void {
    $rendered = markdownRenderer()->render(
        "[attributs](docs/attributs.md), [externe](https://example.com) et [ancre](#profils)",
        ['docs/attributs.md' => '/docs/attributs'],
    );

    expect($rendered['html'])->toContain('<a href="/docs/attributs">attributs</a>')
        ->and($rendered['html'])->toContain('<a href="https://example.com">externe</a>')
        ->and($rendered['html'])->toContain('<a href="#profils">ancre</a>');
});

test('une cible locale inconnue devient un libellé sans lien', function (): void {
    $rendered = markdownRenderer()->render('[introuvable](docs/absent.md)');

    expect($rendered['html'])->toContain('<p>introuvable</p>')
        ->and($rendered['html'])->not->toContain('<a href');
});

test('les paragraphes consécutifs sont joints', function (): void {
    $rendered = markdownRenderer()->render("Une phrase\nsur deux lignes.\n");

    expect($rendered['html'])->toBe("<p>Une phrase sur deux lignes.</p>\n");
});
