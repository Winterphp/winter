<?php

declare(strict_types=1);

namespace Winter\Event;

final class ApplicationEventPublisher
{
    public function __construct(private readonly EventDispatcher $dispatcher)
    {
    }

    public function publish(object $event): void
    {
        $this->dispatcher->dispatch($event);
    }
}
