<?php

declare(strict_types=1);

namespace Winter\Container;

use Psr\Container\ContainerInterface;

final class Container implements ContainerInterface
{
    /**
     * @param array<string, mixed> $definitions
     */
    public function __construct(
        private readonly ContainerInterface $inner,
        private readonly Manifest $manifest,
        private readonly array $definitions = [],
    ) {
    }

    public function get(string $id): mixed
    {
        return $this->inner->get($id);
    }

    public function has(string $id): bool
    {
        return $this->inner->has($id);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $expected
     *
     * @return T
     */
    public function instance(string $id, string $expected): object
    {
        $service = $this->get($id);

        if (!$service instanceof $expected) {
            throw new \RuntimeException(sprintf(
                'Service "%s" is a %s, expected %s.',
                $id,
                get_debug_type($service),
                $expected,
            ));
        }

        return $service;
    }

    public function manifest(): Manifest
    {
        return $this->manifest;
    }

    /**
     * @return list<string>
     */
    public function definitionIds(): array
    {
        return array_keys($this->definitions);
    }
}
