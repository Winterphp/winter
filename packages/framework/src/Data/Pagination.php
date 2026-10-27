<?php

declare(strict_types=1);

namespace Winter\Data;

final class Pagination implements \JsonSerializable
{
    /**
     * @param list<object> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
    ) {
    }

    public function lastPage(): int
    {
        return max(1, (int) ceil($this->total / max(1, $this->perPage)));
    }

    /**
     * @return array{items: list<object>, total: int, page: int, perPage: int, lastPage: int}
     */
    public function jsonSerialize(): array
    {
        return [
            'items' => $this->items,
            'total' => $this->total,
            'page' => $this->page,
            'perPage' => $this->perPage,
            'lastPage' => $this->lastPage(),
        ];
    }
}
