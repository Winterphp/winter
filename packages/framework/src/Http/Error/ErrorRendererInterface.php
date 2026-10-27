<?php

declare(strict_types=1);

namespace Winter\Http\Error;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface ErrorRendererInterface
{
    public function supports(Request $request): bool;

    public function render(\Throwable $exception, Request $request, bool $debug): Response;
}
