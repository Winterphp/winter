<?php

declare(strict_types=1);

namespace App\Documentation;

use Winter\Attribute\Service;

/**
 * Coloration syntaxique des blocs de code de la documentation, avec les
 * classes `.tok-*` de la page d'accueil.
 *
 * La sortie est toujours du HTML sûr : chaque fragment est échappé avant
 * d'être enveloppé dans son `span`, et le texte ne subit aucune autre
 * transformation — le code affiché est identique au code source.
 *
 * Un langage non reconnu (ou absent) est rendu en texte échappé, sans span.
 */
#[Service]
final class CodeHighlighter
{
    /**
     * Langages reconnus, alias compris.
     *
     * @var array<string, string>
     */
    private const LANGUAGES = [
        'php' => 'php',
        'bash' => 'bash',
        'sh' => 'bash',
        'shell' => 'bash',
        'dotenv' => 'dotenv',
        'env' => 'dotenv',
        'tree' => 'tree',
    ];

    /**
     * Types scalaires : tokenisés en T_STRING, donc listés explicitement.
     *
     * @var list<string>
     */
    private const SCALAR_TYPES = [
        'int', 'float', 'string', 'bool', 'array', 'iterable', 'object', 'mixed',
        'void', 'callable', 'never', 'null', 'false', 'true', 'self', 'parent', 'static',
    ];

    /**
     * Jetons rendus comme mots-clés.
     *
     * @var list<int>
     */
    private const KEYWORD_TOKENS = [
        \T_ABSTRACT, \T_ARRAY, \T_AS, \T_BREAK, \T_CALLABLE, \T_CASE, \T_CATCH, \T_CLASS,
        \T_CLONE, \T_CONST, \T_CONTINUE, \T_DECLARE, \T_DEFAULT, \T_DO, \T_ECHO, \T_ELSE,
        \T_ELSEIF, \T_ENUM, \T_EXTENDS, \T_FINAL, \T_FINALLY, \T_FN, \T_FOR,
        \T_FOREACH, \T_FUNCTION, \T_GLOBAL, \T_IF, \T_IMPLEMENTS, \T_INSTANCEOF, \T_INSTEADOF,
        \T_INTERFACE, \T_ISSET, \T_LIST, \T_MATCH, \T_NAMESPACE, \T_NEW, \T_PRINT,
        \T_PRIVATE, \T_PROTECTED, \T_PUBLIC, \T_READONLY, \T_RETURN, \T_STATIC, \T_THROW,
        \T_TRAIT, \T_TRY, \T_UNSET, \T_USE, \T_VAR, \T_WHILE, \T_YIELD,
    ];

    public function highlight(string $code, string $language): string
    {
        return match (self::LANGUAGES[strtolower(trim($language))] ?? null) {
            'php' => $this->php($code),
            'bash' => $this->bash($code),
            'dotenv' => $this->dotenv($code),
            'tree' => $this->tree($code),
            default => $this->escape($code),
        };
    }

    private function php(string $code): string
    {
        // Un extrait qui déclare déjà <?php est tokenisé tel quel ; sinon le
        // préfixe n'existe que pour le tokenizer et disparaît de la sortie.
        $selfContained = str_starts_with($code, '<?php');
        $tokens = token_get_all($selfContained ? $code : '<?php' . "\n" . $code);
        $html = '';
        $attribute = 0;
        $attributeParen = 0;
        $expectFunction = false;

        foreach ($tokens as $index => $token) {
            $id = \is_array($token) ? $token[0] : null;
            $text = \is_array($token) ? $token[1] : $token;

            if ($index === 0 && $id === \T_OPEN_TAG) {
                if (!$selfContained) {
                    continue;
                }

                $html .= $this->span('tok-key', $text);

                continue;
            }

            if ($id === \T_ATTRIBUTE) {
                $html .= $this->span('tok-attr', $text);
                $attribute = 1;
                $attributeParen = 0;

                continue;
            }

            if ($attribute > 0) {
                if ($text === '[') {
                    $attribute++;
                    $html .= $this->escape($text);

                    continue;
                }

                if ($text === ']') {
                    $attribute--;
                    $html .= $this->span('tok-attr', $text);

                    continue;
                }

                if ($text === '(') {
                    $attributeParen++;
                    $html .= $this->escape($text);

                    continue;
                }

                if ($text === ')') {
                    $attributeParen--;
                    $html .= $attributeParen <= 0
                        ? $this->span('tok-attr', $text)
                        : $this->escape($text);

                    continue;
                }

                if ($attribute === 1 && $attributeParen === 0 && $this->isName($id)) {
                    $expectFunction = false;
                    $html .= $this->span('tok-attr', $text);

                    continue;
                }
            }

            if ($id === \T_WHITESPACE) {
                $html .= $this->escape($text);

                continue;
            }

            if ($id === \T_COMMENT || $id === \T_DOC_COMMENT) {
                $expectFunction = false;
                $html .= $this->span('tok-dim', $text);

                continue;
            }

            if ($id === \T_VARIABLE) {
                $expectFunction = false;
                $html .= $this->span('tok-dim', $text);

                continue;
            }

            if ($id === \T_CONSTANT_ENCAPSED_STRING || $id === \T_ENCAPSED_AND_WHITESPACE) {
                $expectFunction = false;
                $html .= $this->span('tok-str', $text);

                continue;
            }

            if ($id === \T_FUNCTION || $id === \T_FN) {
                $html .= $this->span('tok-key', $text);
                $expectFunction = true;

                continue;
            }

            if ($this->isName($id)) {
                $class = $this->nameClass($text, $id, $tokens, $index, $expectFunction);
                $expectFunction = false;
                $html .= $this->span($class, $text);

                continue;
            }

            if ($id !== null && \in_array($id, self::KEYWORD_TOKENS, true)) {
                $expectFunction = false;
                $html .= $this->span('tok-key', $text);

                continue;
            }

            $expectFunction = false;
            $html .= $this->escape($text);
        }

        return $html;
    }

    /**
     * Nom de classe, d'interface, de fonction ou de méthode.
     *
     * @param list<array{int, string, int}|string> $tokens
     */
    private function nameClass(string $text, ?int $id, array $tokens, int $index, bool $expectFunction): string
    {
        if ($expectFunction) {
            return 'tok-fn';
        }

        $previous = $this->significant($tokens, $index, -1);
        $next = $this->significant($tokens, $index, 1);

        if ($previous !== null
            && \in_array($previous, ['->', '?->', '::'], true)
            && $next === '('
        ) {
            return 'tok-fn';
        }

        if ($id === \T_STRING && \in_array(strtolower($text), self::SCALAR_TYPES, true)) {
            return 'tok-key';
        }

        return 'tok-type';
    }

    /**
     * Prochain jeton significatif (texte) dans une direction.
     *
     * @param list<array{int, string, int}|string> $tokens
     */
    private function significant(array $tokens, int $index, int $direction): ?string
    {
        $count = \count($tokens);

        for ($i = $index + $direction; $i >= 0 && $i < $count; $i += $direction) {
            $token = $tokens[$i];
            $id = \is_array($token) ? $token[0] : null;

            if ($id === \T_WHITESPACE || $id === \T_COMMENT || $id === \T_DOC_COMMENT) {
                continue;
            }

            return \is_array($token) ? $token[1] : $token;
        }

        return null;
    }

    private function isName(?int $id): bool
    {
        return $id === \T_STRING || $id === \T_NAME_QUALIFIED || $id === \T_NAME_FULLY_QUALIFIED;
    }

    private function bash(string $code): string
    {
        return implode("\n", array_map($this->bashLine(...), explode("\n", $code)));
    }

    private function bashLine(string $line): string
    {
        [$command, $comment] = $this->splitComment($line);

        $parts = preg_split(
            '/(\s+|&&|\|\||\||>>|>|"[^"]*"|\'[^\']*\')/u',
            $command,
            -1,
            \PREG_SPLIT_DELIM_CAPTURE | \PREG_SPLIT_NO_EMPTY,
        );

        if ($parts === false) {
            return $this->escape($line);
        }

        $html = '';
        $expectCommand = true;
        $expectSubcommand = false;

        foreach ($parts as $part) {
            if (preg_match('/^\s+$/u', $part) === 1) {
                $html .= $this->escape($part);

                continue;
            }

            if (preg_match('/^(&&|\|\||\||;|>>|>)$/', $part) === 1) {
                $expectCommand = true;
                $expectSubcommand = false;
                $html .= $this->escape($part);

                continue;
            }

            if (preg_match('/^(["\']).*\1$/su', $part) === 1) {
                $expectCommand = false;
                $expectSubcommand = false;
                $html .= $this->span('tok-str', $part);

                continue;
            }

            if (str_starts_with($part, '-')) {
                $expectCommand = false;
                $expectSubcommand = false;
                $html .= $this->span('tok-attr', $part);

                continue;
            }

            if ($expectCommand) {
                $expectCommand = false;
                $expectSubcommand = true;
                $html .= $this->span('tok-fn', $part);

                continue;
            }

            if ($expectSubcommand) {
                if (preg_match('/^[a-z][a-z0-9:_-]*$/', $part) === 1) {
                    $expectSubcommand = false;
                    $html .= $this->span('tok-key', $part);

                    continue;
                }

                // Un chemin est ignoré sans consommer l'attente : la
                // sous-commande le suit (`php bin/console cache:compile`).
                if (!str_contains($part, '/') && !str_contains($part, '.')) {
                    $expectSubcommand = false;
                }

                $html .= $this->escape($part);

                continue;
            }

            $html .= $this->escape($part);
        }

        if ($comment !== '') {
            $html .= $this->span('tok-dim', $comment);
        }

        return $html;
    }

    private function dotenv(string $code): string
    {
        return implode("\n", array_map($this->dotenvLine(...), explode("\n", $code)));
    }

    private function dotenvLine(string $line): string
    {
        if (trim($line) === '') {
            return $this->escape($line);
        }

        [$content, $comment] = $this->splitComment($line);
        $tail = $comment === '' ? '' : $this->span('tok-dim', $comment);

        if (trim($content) === '') {
            return $tail;
        }

        if (preg_match('/^(\s*)(export\s+)?([A-Za-z_][A-Za-z0-9_.]*)( *)=(.*?)(\s*)$/', $content, $matches) !== 1) {
            return $this->escape($content) . $tail;
        }

        $value = $matches[5];

        return $this->escape($matches[1])
            . $this->escape($matches[2])
            . $this->span('tok-key', $matches[3])
            . $this->escape($matches[4] . '=')
            . ($value === '' ? '' : $this->span('tok-str', $value))
            . $this->escape($matches[6])
            . $tail;
    }

    private function tree(string $code): string
    {
        return implode("\n", array_map($this->treeLine(...), explode("\n", $code)));
    }

    private function treeLine(string $line): string
    {
        if (trim($line) === '') {
            return $this->escape($line);
        }

        if (preg_match('/^([\s│├└─]*)(.*)$/u', $line, $matches) !== 1) {
            return $this->escape($line);
        }

        $prefix = $matches[1];
        [$content, $comment] = $this->splitComment($matches[2]);

        if (trim($content) === '') {
            return ($prefix === '' ? '' : $this->span('tok-dim', $prefix)) . $this->span('tok-dim', $comment);
        }

        $html = $prefix === '' ? '' : $this->span('tok-dim', $prefix);
        $annotation = '';

        // Espaces d'alignement réservés à l'annotation de droite.
        if (preg_match('/^(.*?\S)(\s*)$/su', $content, $aligned) === 1) {
            $content = $aligned[1];
            $annotation = $aligned[2];
        }

        if ($comment !== '') {
            $annotation .= $comment;
        } elseif (preg_match('/^(.*?\S)(\s{2,})(\S.*)$/su', $content, $gap) === 1) {
            $content = $gap[1];
            $annotation = $gap[2] . $gap[3];
        }

        $html .= $this->treeNode($content);

        return $annotation === '' ? $html : $html . $this->span('tok-dim', $annotation);
    }

    private function treeNode(string $node): string
    {
        if (preg_match('/^([A-Za-z_][A-Za-z0-9_\\\\]*)::([A-Za-z_][A-Za-z0-9_]*)\((.*)\)$/u', $node, $matches) === 1) {
            return $this->span('tok-type', $matches[1])
                . $this->escape('::')
                . $this->span('tok-fn', $matches[2])
                . $this->escape('(')
                . $this->treeArguments($matches[3])
                . $this->escape(')');
        }

        return $this->treePath($node);
    }

    private function treeArguments(string $arguments): string
    {
        return (string) preg_replace_callback(
            '/\b([A-Z][A-Za-z0-9_]*)\b/u',
            fn (array $matches): string => $this->span('tok-type', $matches[1]),
            $this->escape($arguments),
        );
    }

    private function treePath(string $node): string
    {
        $items = preg_split('/(,\s*)/u', $node, -1, \PREG_SPLIT_DELIM_CAPTURE | \PREG_SPLIT_NO_EMPTY);

        if ($items === false) {
            return $this->escape($node);
        }

        $html = '';

        foreach ($items as $item) {
            $html .= preg_match('/^,\s*$/u', $item) === 1
                ? $this->escape($item)
                : $this->treeSegments($item);
        }

        return $html;
    }

    private function treeSegments(string $item): string
    {
        $parts = explode('/', $item);
        $last = \count($parts) - 1;
        $directory = str_ends_with($item, '/');
        $html = '';

        foreach ($parts as $index => $part) {
            if ($index > 0) {
                $html .= $this->escape('/');
            }

            if ($part === '') {
                continue;
            }

            $html .= $this->span($directory || $index < $last ? 'tok-key' : 'tok-type', $part);
        }

        return $html;
    }

    /**
     * Sépare le code d'un commentaire `#` hors quotes.
     *
     * @return array{0: string, 1: string}
     */
    private function splitComment(string $line): array
    {
        $single = false;
        $double = false;
        $length = \strlen($line);

        for ($i = 0; $i < $length; $i++) {
            $char = $line[$i];

            if ($char === '\\') {
                $i++;

                continue;
            }

            if ($char === "'" && !$double) {
                $single = !$single;

                continue;
            }

            if ($char === '"' && !$single) {
                $double = !$double;

                continue;
            }

            if ($char === '#' && !$single && !$double) {
                return [substr($line, 0, $i), substr($line, $i)];
            }
        }

        return [$line, ''];
    }

    private function span(string $class, string $text): string
    {
        return $text === '' ? '' : sprintf('<span class="%s">%s</span>', $class, $this->escape($text));
    }

    private function escape(string $text): string
    {
        return htmlspecialchars($text, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
    }
}
