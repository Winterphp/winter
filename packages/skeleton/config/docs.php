<?php

declare(strict_types=1);

/**
 * Documentation publiée par le site /docs.
 *
 * - path  : dossier contenant les fichiers Markdown des pages
 * - entry : page de présentation (README), facultative
 *
 * Le premier dossier existant gagne : docs/ de la racine du monorepo,
 * puis docs/ du squelette pour une installation autonome.
 */
$candidates = [
    \dirname(__DIR__, 3) . '/docs',
    \dirname(__DIR__) . '/docs',
];

$path = '';
$entry = '';

foreach ($candidates as $candidate) {
    if (!is_dir($candidate)) {
        continue;
    }

    $path = $candidate;
    $readme = \dirname($candidate) . '/README.md';

    if (is_file($readme)) {
        $entry = $readme;
    }

    break;
}

return [
    'path' => $path,
    'entry' => $entry,
];
