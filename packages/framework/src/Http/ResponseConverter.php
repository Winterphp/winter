<?php

declare(strict_types=1);

namespace Winter\Http;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Winter\View\View;
use Winter\View\ViewRenderer;

final class ResponseConverter
{
    public function __construct(
        private readonly ViewRenderer $views,
        private readonly SerializerInterface $serializer,
    ) {
    }

    /**
     * @param array<string, string> $headers
     */
    public function convert(mixed $result, ?string $template = null, int $status = 200, array $headers = []): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if ($result instanceof View) {
            return $this->views->render($result->template, $result->context, $result->status, $result->headers);
        }

        if ($template !== null) {
            $context = \is_array($result) ? $result : ['result' => $result];

            return $this->views->render($template, $context, $status, $headers);
        }

        if ($result === null) {
            return new Response('', 204, $headers);
        }

        if (\is_string($result)) {
            return new Response($result, $status, $headers);
        }

        $json = $this->serializer->serialize($result, 'json');

        return new Response($json, $status, ['Content-Type' => 'application/json'] + $headers);
    }
}
