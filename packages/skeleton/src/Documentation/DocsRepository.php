<?php

declare(strict_types=1);

namespace App\Documentation;

use Winter\Attribute\Service;
use Winter\Attribute\Value;
use Winter\Http\RouteGenerator;

/**
 * Catalogue et lecture des pages de documentation publiées par /docs.
 *
 * Les fichiers Markdown vivent hors du dossier public : aucun chemin n'est
 * construit depuis l'URL, seul le catalogue ci-dessous est consultable.
 */
#[Service]
final class DocsRepository
{
    /**
     * @var array<string, array{file: string, entry: bool, label: string, summary: string}>
     */
    private const PAGES = [
        'introduction' => [
            'file' => 'README.md',
            'entry' => true,
            'label' => 'Présentation',
            'summary' => 'Ce que fait Winter, installation du monorepo et commandes de la console.',
        ],
        'architecture' => [
            'file' => 'architecture.md',
            'entry' => false,
            'label' => 'Architecture',
            'summary' => 'Cycle d’une requête, compilation du container, profils, événements et limites assumées.',
        ],
        'attributs' => [
            'file' => 'attributs.md',
            'entry' => false,
            'label' => 'Référence des attributs',
            'summary' => 'Chaque attribut Winter : stéréotypes, injection, web, données, avis d’exception, profils.',
        ],
        'migration-depuis-laravel' => [
            'file' => 'migration-depuis-laravel.md',
            'entry' => false,
            'label' => 'Venir de Laravel',
            'summary' => 'Correspondances Laravel → Winter et ce qu’il faut accepter en changeant de framework.',
        ],
        'audit-securite' => [
            'file' => 'audit-securite.md',
            'entry' => false,
            'label' => 'Audit de sécurité',
            'summary' => 'Constats de l’audit, risques acceptés, points vérifiés sans anomalie et vérifications à rejouer.',
        ],
    ];

    public function __construct(
        #[Value('docs.path', '')]
        private readonly string $docsPath,
        #[Value('docs.entry', '')]
        private readonly string $entryFile,
        private readonly MarkdownRenderer $markdown,
        private readonly RouteGenerator $routes,
    ) {
    }

    /**
     * @return list<array{slug: string, label: string, summary: string}>
     */
    public function pages(): array
    {
        $pages = [];

        foreach (self::PAGES as $slug => $page) {
            $pages[] = ['slug' => $slug, 'label' => $page['label'], 'summary' => $page['summary']];
        }

        return $pages;
    }

    public function page(string $slug): ?DocPage
    {
        $definition = self::PAGES[$slug] ?? null;

        if ($definition === null) {
            return null;
        }

        $file = $this->file($definition);
        $markdown = $file === null ? false : @file_get_contents($file);

        if ($markdown === false) {
            return null;
        }

        $rendered = $this->markdown->render($markdown, $this->links());

        return new DocPage(
            slug: $slug,
            label: $definition['label'],
            title: self::title($markdown) ?? $definition['label'],
            summary: $definition['summary'],
            source: $definition['entry'] ? 'README.md' : 'docs/' . $definition['file'],
            html: $rendered['html'],
            toc: $rendered['toc'],
        );
    }

    /**
     * Page voisine dans l'ordre de lecture, pour la pagination.
     *
     * @return array{slug: string, label: string}|null
     */
    public function sibling(string $slug, int $offset): ?array
    {
        $slugs = array_keys(self::PAGES);
        $index = array_search($slug, $slugs, true);

        if ($index === false) {
            return null;
        }

        $neighbour = $slugs[$index + $offset] ?? null;

        if ($neighbour === null) {
            return null;
        }

        return ['slug' => $neighbour, 'label' => self::PAGES[$neighbour]['label']];
    }

    /**
     * @param array{file: string, entry: bool, label: string, summary: string} $definition
     */
    private function file(array $definition): ?string
    {
        if ($definition['entry']) {
            return $this->entryFile === '' ? null : $this->entryFile;
        }

        if ($this->docsPath === '') {
            return null;
        }

        return $this->docsPath . '/' . $definition['file'];
    }

    /**
     * Table de correspondance des liens Markdown vers les pages du site.
     *
     * @return array<string, string>
     */
    private function links(): array
    {
        $links = [];

        foreach (self::PAGES as $slug => $page) {
            $url = $this->routes->path('docs.show', ['slug' => $slug]);
            $links[$page['file']] = $url;

            if (!$page['entry']) {
                $links['docs/' . $page['file']] = $url;
            }
        }

        return $links;
    }

    private static function title(string $markdown): ?string
    {
        if (preg_match('/^#\s+(.+)$/m', $markdown, $matches) !== 1) {
            return null;
        }

        $title = trim($matches[1]);

        return $title === '' ? null : $title;
    }
}
