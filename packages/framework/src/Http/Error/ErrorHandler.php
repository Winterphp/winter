<?php

declare(strict_types=1);

namespace Winter\Http\Error;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Winter\Http\HttpException\HttpException;

final class ErrorHandler
{
    public function __construct(
        private readonly HtmlErrorRenderer $html,
        private readonly JsonErrorRenderer $json,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function render(\Throwable $exception, Request $request, bool $debug): Response
    {
        $status = $exception instanceof HttpException ? $exception->getStatusCode() : 500;

        if ($status >= 500) {
            $this->logger->error($exception->getMessage(), [
                'exception' => $exception,
                'method' => $request->getMethod(),
                'path' => $request->getPathInfo(),
            ]);
        }

        $renderer = $this->json->supports($request) ? $this->json : $this->html;
        $response = $renderer->render($exception, $request, $debug);

        if ($exception instanceof HttpException) {
            foreach ($exception->getHeaders() as $name => $value) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }
}
