<?php

declare(strict_types=1);

namespace Winter\Tests\Fixtures\App\Repository;

use Winter\Tests\Fixtures\App\Entity\Note;

interface NoteRepositoryInterface
{
    public function create(string $title): Note;

    /**
     * @return list<Note>
     */
    public function all(): array;
}
