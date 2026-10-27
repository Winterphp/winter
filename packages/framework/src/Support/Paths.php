<?php

declare(strict_types=1);

namespace Winter\Support;

final class Paths
{
    public function __construct(public readonly string $root)
    {
    }

    public function src(): string
    {
        return $this->root . '/src';
    }

    public function templates(): string
    {
        return $this->root . '/templates';
    }

    public function var(): string
    {
        return $this->root . '/var';
    }

    public function cache(): string
    {
        return $this->var() . '/cache';
    }

    public function log(): string
    {
        return $this->var() . '/log';
    }

    public function manifestFile(): string
    {
        return $this->cache() . '/manifest.php';
    }

    /**
     * Crée le dossier s'il n'existe pas et retourne son chemin.
     */
    public function ensure(string $dir): string
    {
        if (!is_dir($dir) && !mkdir($dir, 0o775, true) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('Unable to create directory "%s".', $dir));
        }

        return $dir;
    }
}
