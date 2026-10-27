<?php

declare(strict_types=1);

namespace Winter\View;

use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class ViewRenderer
{
    public function __construct(private readonly Environment $twig)
    {
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, string> $headers
     */
    public function render(string $template, array $context = [], int $status = 200, array $headers = []): Response
    {
        return new Response(
            $this->twig->render($template, $context),
            $status,
            $headers + ['Content-Type' => 'text/html; charset=UTF-8'],
        );
    }

    public function twig(): Environment
    {
        return $this->twig;
    }
}
