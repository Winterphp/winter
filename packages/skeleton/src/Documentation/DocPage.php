<?php

declare(strict_types=1);

namespace App\Documentation;

/**
 * Une page de documentation prête à être affichée.
 */
final class DocPage
{
    /**
     * @param list<array{level: int, id: string, title: string}> $toc
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $label,
        public readonly string $title,
        public readonly string $summary,
        public readonly string $source,
        public readonly string $html,
        public readonly array $toc,
    ) {
    }
}
