<?php

declare(strict_types=1);

namespace Winter\Http;

use Symfony\Component\HttpFoundation\Response;
use Winter\Container\Container;

final class ExceptionHandlerResolver
{
    /**
     * @param list<array{class: string, method: string, exception: string, order: int, status: ?int}> $handlers
     */
    public function __construct(
        private readonly array $handlers,
        private readonly Container $container,
        private readonly ArgumentResolver $arguments,
        private readonly ResponseConverter $converter,
    ) {
    }

    public function resolve(\Throwable $exception, RequestContext $context): ?Response
    {
        $handler = $this->find($exception);

        if ($handler === null) {
            return null;
        }

        $advice = $this->container->get($handler['class']);

        if (!\is_object($advice)) {
            return null;
        }

        $parameters = $this->arguments->resolve(
            new \ReflectionMethod($handler['class'], $handler['method']),
            $context,
        );

        $result = $advice->{$handler['method']}(...$parameters);

        return $this->converter->convert($result, null, $handler['status'] ?? 200);
    }

    /**
     * @return array{class: string, method: string, exception: string, order: int, status: ?int}|null
     */
    private function find(\Throwable $exception): ?array
    {
        /** @var list<string> $types */
        $types = array_merge(
            [$exception::class],
            array_values(class_parents($exception)),
            array_values(class_implements($exception)),
        );

        foreach ($types as $type) {
            $matching = array_values(array_filter(
                $this->handlers,
                static fn (array $handler): bool => $handler['exception'] === $type,
            ));

            if ($matching === []) {
                continue;
            }

            usort($matching, static fn (array $a, array $b): int => $a['order'] <=> $b['order']);

            return $matching[0];
        }

        return null;
    }
}
