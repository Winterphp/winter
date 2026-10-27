<?php

declare(strict_types=1);

namespace Winter\Tests\Fixtures\App\Controller;

use Winter\Attribute\Controller;
use Winter\Attribute\GetMapping;
use Winter\Attribute\PostMapping;
use Winter\Attribute\RequestBody;
use Winter\Attribute\Route;
use Winter\Tests\Fixtures\App\Dto\CreateNoteRequest;
use Winter\Tests\Fixtures\App\Repository\NoteRepositoryInterface;

#[Controller]
#[Route('/notes')]
final class NoteController
{
    public function __construct(private readonly NoteRepositoryInterface $notes)
    {
    }

    #[GetMapping('')]
    public function index(): array
    {
        return array_map(
            static fn ($note): array => ['id' => $note->getId(), 'title' => $note->getTitle()],
            $this->notes->all(),
        );
    }

    #[PostMapping('')]
    public function create(#[RequestBody] CreateNoteRequest $request): array
    {
        $note = $this->notes->create($request->title);

        return ['id' => $note->getId(), 'title' => $note->getTitle()];
    }
}
