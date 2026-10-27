<?php

declare(strict_types=1);

use App\Documentation\CodeHighlighter;

test('la coloration PHP suit la palette de la page d’accueil', function (): void {
    $html = (new CodeHighlighter())->highlight(<<<'PHP'
#[Repository]
final class DoctrineUserRepository extends Repository implements UserRepositoryInterface
{
    #[ExceptionHandler(NotFoundHttpException::class)]
    public function notFound(string $id): array
    {
        return ['error' => 'not_found', 'path' => $this->articles->find($id)];
    }
}
PHP, 'php');

    expect($html)->toContain('<span class="tok-attr">#[</span><span class="tok-attr">Repository</span>')
        ->and($html)->toContain('<span class="tok-key">final</span> <span class="tok-key">class</span> <span class="tok-type">DoctrineUserRepository</span>')
        ->and($html)->toContain('<span class="tok-key">extends</span> <span class="tok-type">Repository</span>')
        ->and($html)->toContain('<span class="tok-key">implements</span> <span class="tok-type">UserRepositoryInterface</span>')
        ->and($html)->toContain('<span class="tok-attr">ExceptionHandler</span>(<span class="tok-type">NotFoundHttpException</span>::<span class="tok-key">class</span><span class="tok-attr">)</span><span class="tok-attr">]</span>')
        ->and($html)->toContain('<span class="tok-key">function</span> <span class="tok-fn">notFound</span>')
        ->and($html)->toContain('<span class="tok-key">string</span> <span class="tok-dim">$id</span>')
        ->and($html)->toContain('<span class="tok-key">array</span>')
        ->and($html)->toContain('<span class="tok-key">return</span> [<span class="tok-str">&#039;error&#039;</span> =&gt; <span class="tok-str">&#039;not_found&#039;</span>')
        ->and($html)->toContain('<span class="tok-dim">$this</span>-&gt;<span class="tok-type">articles</span>-&gt;<span class="tok-fn">find</span>(<span class="tok-dim">$id</span>)');
});

test('la coloration bash distingue commande, sous-commande, drapeau et commentaire', function (): void {
    $html = (new CodeHighlighter())->highlight(
        "composer test                     # Pest : framework + squelette\n"
        . "php bin/console db:diff && php bin/console db:migrate\n"
        . "php -S 127.0.0.1:8000 -t public\n",
        'bash',
    );

    expect($html)->toContain('<span class="tok-fn">composer</span> <span class="tok-key">test</span>')
        ->and($html)->toContain('<span class="tok-dim"># Pest : framework + squelette</span>')
        ->and($html)->toContain('<span class="tok-fn">php</span> bin/console <span class="tok-key">db:diff</span>')
        ->and($html)->toContain('<span class="tok-fn">php</span> bin/console <span class="tok-key">db:migrate</span>')
        ->and($html)->toContain('<span class="tok-fn">php</span> <span class="tok-attr">-S</span> 127.0.0.1:8000 <span class="tok-attr">-t</span> public');
});

test('la coloration dotenv sépare clé et valeur', function (): void {
    $html = (new CodeHighlighter())->highlight("APP_ENV=staging\nAPP_PROFILES=debug,metrics\nJWT_TTL=\n", 'dotenv');

    expect($html)->toContain('<span class="tok-key">APP_ENV</span>=<span class="tok-str">staging</span>')
        ->and($html)->toContain('<span class="tok-key">APP_PROFILES</span>=<span class="tok-str">debug,metrics</span>')
        ->and($html)->toContain('<span class="tok-key">JWT_TTL</span>=');
});

test('la coloration d’arborescence distingue dossiers, fichiers, appels et annotations', function (): void {
    $html = (new CodeHighlighter())->highlight(
        "public/index.php\n"
        . "└── Application::run()\n"
        . "    ├── ContainerBuilder         PHP-DI, compilé en prod\n"
        . "config/\n"
        . "  app.php          # toujours chargé\n"
        . "var/cache, var/log     # exécution\n",
        'tree',
    );

    expect($html)->toContain('<span class="tok-key">public</span>/<span class="tok-type">index.php</span>')
        ->and($html)->toContain('<span class="tok-dim">└── </span><span class="tok-type">Application</span>::<span class="tok-fn">run</span>()')
        ->and($html)->toContain('<span class="tok-dim">    ├── </span><span class="tok-type">ContainerBuilder</span>')
        ->and($html)->toContain('PHP-DI, compilé en prod</span>')
        ->and($html)->toContain('<span class="tok-key">config</span>/')
        ->and($html)->toContain('<span class="tok-type">app.php</span>')
        ->and($html)->toContain('# toujours chargé</span>')
        ->and($html)->toContain('<span class="tok-key">var</span>/<span class="tok-type">cache</span>, <span class="tok-key">var</span>/<span class="tok-type">log</span>');
});

test('un langage inconnu ou absent est rendu en texte échappé', function (): void {
    $highlighter = new CodeHighlighter();

    expect($highlighter->highlight('<script>', ''))->toBe('&lt;script&gt;')
        ->and($highlighter->highlight('a > b', 'json'))->toBe('a &gt; b')
        ->and($highlighter->highlight('<?php echo 1;', 'pas-un-langage'))->toBe('&lt;?php echo 1;')
        ->and($highlighter->highlight('<b>x</b>', 'tree'))->not->toContain('<b>');
});

test('les alias de langage et le préfixe <?php sont gérés', function (): void {
    $highlighter = new CodeHighlighter();

    expect($highlighter->highlight("composer install\n", 'shell'))->toContain('<span class="tok-fn">composer</span>')
        ->and($highlighter->highlight("composer install\n", 'BASH'))->toContain('<span class="tok-fn">composer</span>')
        ->and($highlighter->highlight("KEY=value\n", 'env'))->toContain('<span class="tok-key">KEY</span>')
        ->and($highlighter->highlight("<?php\nreturn [];\n", 'php'))->toContain('<span class="tok-key">&lt;?php')
        ->and($highlighter->highlight("<?php\nreturn [];\n", 'php'))->toContain('<span class="tok-key">return</span>');
});

test('un bloc php déjà préfixé ne double pas <?php', function (): void {
    $html = (new CodeHighlighter())->highlight("<?php\necho 1;\n", 'php');

    expect(substr_count($html, '&lt;?php'))->toBe(1);
});

/**
 * Tous les blocs de code des documents publiés, dans l'ordre : [langage, contenu].
 *
 * @return list<array{0: string, 1: string}>
 */
function documentationFences(): array
{
    $root = \dirname(__DIR__, 4);
    $files = [
        $root . '/README.md',
        $root . '/docs/architecture.md',
        $root . '/docs/attributs.md',
        $root . '/docs/migration-depuis-laravel.md',
        $root . '/docs/audit-securite.md',
    ];

    $fences = [];

    foreach ($files as $file) {
        $language = '';
        $buffer = [];
        $open = false;

        foreach (explode("\n", (string) file_get_contents($file)) as $line) {
            if (str_starts_with(trim($line), '```')) {
                if ($open) {
                    $fences[] = [$language, implode("\n", $buffer)];
                } else {
                    $language = trim(substr(trim($line), 3));
                }

                $open = !$open;
                $buffer = [];

                continue;
            }

            if ($open) {
                $buffer[] = $line;
            }
        }
    }

    return $fences;
}

test('la coloration ne modifie jamais le code source des documents', function (): void {
    $highlighter = new CodeHighlighter();
    $fences = documentationFences();

    expect($fences)->toHaveCount(18);

    foreach ($fences as $index => [$language, $code]) {
        expect(html_entity_decode(strip_tags($highlighter->highlight($code, $language)), \ENT_QUOTES, 'UTF-8'))
            ->toBe($code, sprintf('bloc %d (%s) altéré par la coloration', $index + 1, $language));
    }
});

test('chaque bloc des documents déclare un langage connu', function (): void {
    $counts = array_count_values(array_map(
        static fn (array $fence): string => $fence[0],
        documentationFences(),
    ));

    expect($counts['php'] ?? 0)->toBe(10)
        ->and($counts['bash'] ?? 0)->toBe(3)
        ->and($counts['tree'] ?? 0)->toBe(4)
        ->and($counts['dotenv'] ?? 0)->toBe(1)
        ->and(array_key_exists('', $counts))->toBeFalse();
});
