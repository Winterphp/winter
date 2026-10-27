<?php

declare(strict_types=1);

namespace App\Documentation;

use Winter\Attribute\Service;

/**
 * Convertit le sous-ensemble Markdown utilisé par la documentation Winter :
 * titres `##` / `###`, paragraphes, blocs de code, listes, tableaux,
 * `code inline`, `**gras**` et liens.
 *
 * Le contenu est toujours échappé : aucun HTML brut ne traverserait le rendu.
 * Les titres de niveau 1 sont ignorés, le titre de la page étant porté par le
 * gabarit.
 */
#[Service]
final class MarkdownRenderer
{
    public function __construct(private readonly CodeHighlighter $highlighter)
    {
    }

    /**
     * @param array<string, string> $links cible Markdown => URL du site
     *
     * @return array{html: string, toc: list<array{level: int, id: string, title: string}>}
     */
    public function render(string $markdown, array $links = []): array
    {
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $markdown));
        $count = \count($lines);
        $html = '';
        $toc = [];
        $used = [];
        $paragraph = [];

        $flush = function () use (&$paragraph, &$html, $links): void {
            if ($paragraph === []) {
                return;
            }

            $html .= '<p>' . $this->inline(implode(' ', $paragraph), $links) . '</p>' . "\n";
            $paragraph = [];
        };

        for ($i = 0; $i < $count; $i++) {
            $line = $lines[$i];
            $trimmed = trim($line);

            if ($trimmed === '') {
                $flush();

                continue;
            }

            // Bloc de code délimité par ``` : colorisé selon son langage.
            if (str_starts_with($trimmed, '```')) {
                $flush();
                $language = trim(substr($trimmed, 3));
                $buffer = [];

                for ($i++; $i < $count && !str_starts_with(trim($lines[$i]), '```'); $i++) {
                    $buffer[] = $lines[$i];
                }

                $html .= '<div class="doc-code"><pre><code>'
                    . $this->highlighter->highlight(implode("\n", $buffer), $language)
                    . '</code></pre></div>' . "\n";

                continue;
            }

            // Titre de page : porté par le gabarit, jamais rendu dans le corps.
            if (preg_match('/^#\s+/', $trimmed) === 1) {
                $flush();

                continue;
            }

            if (preg_match('/^(#{2,3})\s+(.+)$/', $trimmed, $matches) === 1) {
                $flush();
                $level = \strlen($matches[1]);
                $title = trim($matches[2]);
                $id = $this->anchor($title, $used);
                $label = $this->inline($title, $links);

                $html .= sprintf('<h%d id="%s">%s</h%d>', $level, $id, $label, $level) . "\n";
                $toc[] = ['level' => $level, 'id' => $id, 'title' => $label];

                continue;
            }

            if (str_starts_with($trimmed, '|')
                && $i + 1 < $count
                && $this->isSeparatorRow($lines[$i + 1])
            ) {
                $flush();
                $header = $this->cells($trimmed);
                $rows = [];

                for ($i += 2; $i < $count && str_starts_with(trim($lines[$i]), '|'); $i++) {
                    $rows[] = $this->cells(trim($lines[$i]));
                }

                $i--;

                $html .= '<div class="doc-table"><table><thead><tr>';

                foreach ($header as $cell) {
                    $html .= '<th>' . $this->inline($cell, $links) . '</th>';
                }

                $html .= '</tr></thead><tbody>';

                foreach ($rows as $row) {
                    $html .= '<tr>';

                    foreach ($row as $cell) {
                        $html .= '<td>' . $this->inline($cell, $links) . '</td>';
                    }

                    $html .= '</tr>';
                }

                $html .= '</tbody></table></div>' . "\n";

                continue;
            }

            if (preg_match('/^([-*]|\d+\.)\s+(.+)$/', $trimmed, $matches) === 1) {
                $flush();
                $ordered = $matches[1] !== '-' && $matches[1] !== '*';
                $items = [];

                for (
                    ; $i < $count && preg_match('/^([-*]|\d+\.)\s+(.+)$/', trim($lines[$i]), $item) === 1;
                    $i++
                ) {
                    $items[] = $item[2];
                }

                $i--;
                $tag = $ordered ? 'ol' : 'ul';
                $html .= '<' . $tag . '>';

                foreach ($items as $item) {
                    $html .= '<li>' . $this->inline($item, $links) . '</li>';
                }

                $html .= '</' . $tag . '>' . "\n";

                continue;
            }

            $paragraph[] = $trimmed;
        }

        $flush();

        return ['html' => $html, 'toc' => $toc];
    }

    /**
     * Mise en forme en ligne : code, gras et liens.
     *
     * @param array<string, string> $links
     */
    private function inline(string $text, array $links): string
    {
        $text = $this->escape($text);

        /** @var array<string, string> $code */
        $code = [];
        $text = (string) preg_replace_callback(
            '/`([^`]+)`/',
            static function (array $matches) use (&$code): string {
                $placeholder = "\x1A" . \count($code) . "\x1A";
                $code[$placeholder] = $matches[1];

                return $placeholder;
            },
            $text,
        );

        $text = (string) preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);

        $text = (string) preg_replace_callback(
            '/\[([^\]]+)\]\(([^)\s]+)\)/',
            function (array $matches) use ($links): string {
                $url = $this->link($matches[2], $links);

                return $url === null ? $matches[1] : sprintf('<a href="%s">%s</a>', $this->escape($url), $matches[1]);
            },
            $text,
        );

        foreach ($code as $placeholder => $snippet) {
            $text = str_replace($placeholder, '<code>' . $snippet . '</code>', $text);
        }

        return $text;
    }

    /**
     * Résout une cible de lien Markdown vers une URL du site.
     *
     * @param array<string, string> $links
     */
    private function link(string $target, array $links): ?string
    {
        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $target) === 1
            || str_starts_with($target, '#')
            || str_starts_with($target, '/')
        ) {
            return $target;
        }

        $key = (string) preg_replace('#^\./#', '', $target);

        if (isset($links[$key])) {
            return $links[$key];
        }

        // Cible locale inconnue : libellé sans lien plutôt qu'une URL cassée.
        return str_ends_with($key, '.md') ? null : $target;
    }

    /**
     * Ancre unique et stable pour un titre.
     *
     * @param array<string, int> $used
     */
    private function anchor(string $title, array &$used): string
    {
        $base = $this->slug($title);

        if ($base === '') {
            $base = 'section';
        }

        $used[$base] = ($used[$base] ?? 0) + 1;

        return $used[$base] > 1 ? $base . '-' . $used[$base] : $base;
    }

    private function slug(string $title): string
    {
        $ascii = \function_exists('iconv')
            ? @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title)
            : false;

        $value = strtolower($ascii === false ? $title : $ascii);

        return trim((string) preg_replace('/[^a-z0-9]+/', '-', $value), '-');
    }

    private function isSeparatorRow(string $line): bool
    {
        $cells = $this->cells(trim($line));

        if ($cells === []) {
            return false;
        }

        foreach ($cells as $cell) {
            if (preg_match('/^:?-{2,}:?$/', $cell) !== 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private function cells(string $line): array
    {
        return array_map('trim', explode('|', trim(trim($line), '|')));
    }

    private function escape(string $text): string
    {
        return htmlspecialchars($text, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
    }
}
