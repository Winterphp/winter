<?php

declare(strict_types=1);

namespace Winter\Event;

use Symfony\Component\HttpFoundation\Request;

final class KernelRequestEvent
{
    public function __construct(public readonly Request $request)
    {
    }
}
