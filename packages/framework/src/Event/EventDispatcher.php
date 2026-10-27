<?php

declare(strict_types=1);

namespace Winter\Event;

use Psr\Container\ContainerInterface;

final class EventDispatcher
{
    /** @var list<array{class: string, method: string, event: string, priority: int}> */
    private array $listeners;

    /**
     * @param list<array{class: string, method: string, event: string, priority: int}> $listeners
     */
    public function __construct(array $listeners, private readonly ContainerInterface $container)
    {
        usort(
            $listeners,
            static fn (array $a, array $b): int => $b['priority'] <=> $a['priority'],
        );

        $this->listeners = $listeners;
    }

    public function dispatch(object $event): object
    {
        foreach ($this->listeners as $listener) {
            if (!$event instanceof $listener['event']) {
                continue;
            }

            $instance = $this->container->get($listener['class']);

            if (!\is_object($instance)) {
                throw new \RuntimeException(sprintf(
                    'Event listener "%s" is not a service instance.',
                    $listener['class'],
                ));
            }

            $instance->{$listener['method']}($event);
        }

        return $event;
    }
}
