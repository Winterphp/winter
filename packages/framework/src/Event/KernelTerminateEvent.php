<?php

declare(strict_types=1);

namespace Winter\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class KernelTerminateEvent
{
    public function __construct(
        public readonly Request $request,
        public readonly Response $response,
    ) {
    }
}
