<?php

declare(strict_types=1);

namespace Winter\Http\Error;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Winter\Http\HttpException\HttpException;
use Winter\Http\HttpException\ValidationException;

final class JsonErrorRenderer implements ErrorRendererInterface
{
    public function supports(Request $request): bool
    {
        $accept = (string) $request->headers->get('Accept', '');

        return str_contains($accept, 'application/json')
            || str_starts_with($request->getPathInfo(), '/api')
            || $request->isXmlHttpRequest();
    }

    public function render(\Throwable $exception, Request $request, bool $debug): Response
    {
        $status = $exception instanceof HttpException ? $exception->getStatusCode() : 500;
        $message = $this->message($exception, $status, $debug);

        $payload = [
            'error' => $this->reason($status),
            'message' => $message,
        ];

        if ($exception instanceof ValidationException) {
            $payload['details'] = $exception->getErrors();
        }

        if ($debug && $status >= 500) {
            $payload['exception'] = $exception::class;
            $payload['trace'] = explode("\n", $exception->getTraceAsString());
        }

        return new JsonResponse($payload, $status);
    }

    private function message(\Throwable $exception, int $status, bool $debug): string
    {
        if ($exception instanceof HttpException && $status < 500) {
            return $exception->getMessage();
        }

        return $debug ? $exception->getMessage() : 'Internal Server Error';
    }

    private function reason(int $status): string
    {
        return Response::$statusTexts[$status] ?? 'Error';
    }
}
