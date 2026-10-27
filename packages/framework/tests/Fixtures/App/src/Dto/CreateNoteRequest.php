<?php

declare(strict_types=1);

namespace Winter\Tests\Fixtures\App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateNoteRequest
{
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(min: 3, max: 255)]
    public string $title = '';
}
