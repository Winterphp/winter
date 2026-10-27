<?php

declare(strict_types=1);

namespace Winter\Tests\Fixtures\App\Repository;

use Winter\Attribute\Repository;
use Winter\Data\Repository as BaseRepository;
use Winter\Tests\Fixtures\App\Entity\Note;

#[Repository]
final class DoctrineNoteRepository extends BaseRepository implements NoteRepositoryInterface
{
    protected function entityClass(): string
    {
        return Note::class;
    }

    public function create(string $title): Note
    {
        $note = new Note($title);
        $this->save($note);

        return $note;
    }

    public function all(): array
    {
        return $this->findAll(['id' => 'ASC']);
    }
}
