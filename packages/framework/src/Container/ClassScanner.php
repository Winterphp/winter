<?php

declare(strict_types=1);

namespace Winter\Container;

final class ClassScanner
{
    /**
     * @param list<string> $paths
     *
     * @return array<string, \ReflectionClass<object>>
     */
    public function scan(array $paths): array
    {
        $found = [];

        foreach ($paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            );

            /** @var \SplFileInfo $file */
            foreach ($iterator as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                foreach ($this->classesInFile($file->getPathname()) as $fqcn) {
                    if (!class_exists($fqcn) && !interface_exists($fqcn) && !enum_exists($fqcn)) {
                        continue;
                    }

                    try {
                        $found[$fqcn] = new \ReflectionClass($fqcn);
                    } catch (\ReflectionException) {
                        continue;
                    }
                }
            }
        }

        ksort($found);

        return $found;
    }

    /**
     * @return list<string>
     */
    public function classesInFile(string $path): array
    {
        $code = @file_get_contents($path);

        if ($code === false) {
            return [];
        }

        $tokens = token_get_all($code);
        $count = \count($tokens);
        $namespace = '';
        $classes = [];
        $previous = null;

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];

            if (!\is_array($token)) {
                if ($token !== '') {
                    $previous = $token;
                }

                continue;
            }

            [$id, $text] = $token;

            if ($id === T_NAMESPACE) {
                $namespace = '';
                for ($j = $i + 1; $j < $count; $j++) {
                    $next = $tokens[$j];

                    if (\is_array($next) && \in_array($next[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NS_SEPARATOR], true)) {
                        $namespace .= $next[1];

                        continue;
                    }

                    if ($next === ';' || $next === '{') {
                        break;
                    }

                    if (\is_array($next) && $next[0] === T_WHITESPACE) {
                        continue;
                    }

                    break;
                }

                $previous = $token;

                continue;
            }

            if (\in_array($id, [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)) {
                $isAnonymous = \is_array($previous) && \in_array($previous[0], [T_NEW, T_DOUBLE_COLON], true);

                if (!$isAnonymous) {
                    for ($j = $i + 1; $j < $count; $j++) {
                        $next = $tokens[$j];

                        if (\is_array($next) && \in_array($next[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                            continue;
                        }

                        if (\is_array($next) && $next[0] === T_STRING) {
                            $name = ltrim($namespace . '\\' . $next[1], '\\');
                            $classes[] = $name;
                        }

                        break;
                    }
                }
            }

            if (!\in_array($id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $previous = $token;
            }
        }

        return $classes;
    }
}
