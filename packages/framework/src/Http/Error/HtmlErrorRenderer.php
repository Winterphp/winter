<?php

declare(strict_types=1);

namespace Winter\Http\Error;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Winter\Http\HttpException\HttpException;
use Winter\Http\HttpException\ValidationException;

final class HtmlErrorRenderer implements ErrorRendererInterface
{
    public function __construct(private readonly Environment $twig)
    {
    }

    public function supports(Request $request): bool
    {
        return true;
    }

    public function render(\Throwable $exception, Request $request, bool $debug): Response
    {
        $status = $exception instanceof HttpException ? $exception->getStatusCode() : 500;

        $context = [
            'status' => $status,
            'message' => $this->message($exception, $status, $debug),
            'exception' => $debug ? $exception : null,
            'exception_class' => $debug ? $exception::class : null,
            'request' => $request,
        ];

        if ($exception instanceof ValidationException) {
            $context['errors'] = $exception->getErrors();
        }

        try {
            $html = $this->twig->render(
                $debug ? '@framework/error/dev.html.twig' : '@framework/error/prod.html.twig',
                $context,
            );
        } catch (\Throwable) {
            $html = sprintf(
                '<!doctype html><html><body><h1>%d</h1><p>%s</p></body></html>',
                $status,
                htmlspecialchars((string) $context['message'], ENT_QUOTES),
            );
        }

        return new Response($html, $status, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    private function message(\Throwable $exception, int $status, bool $debug): string
    {
        if ($exception instanceof HttpException && $status < 500) {
            return $exception->getMessage();
        }

        return $debug ? $exception->getMessage() : 'Internal Server Error';
    }
}
