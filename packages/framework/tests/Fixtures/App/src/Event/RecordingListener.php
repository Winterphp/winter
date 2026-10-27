<?php

declare(strict_types=1);

namespace Winter\Tests\Fixtures\App\Event;

use Winter\Attribute\Component;
use Winter\Attribute\EventListener;
use Winter\Event\KernelBootEvent;
use Winter\Event\KernelRequestEvent;
use Winter\Event\KernelTerminateEvent;

#[Component]
final class RecordingListener
{
    /** @var list<string> */
    public static array $calls = [];

    /** @var list<string> */
    public static array $requests = [];

    /** @var list<int> */
    public static array $terminated = [];

    public static int $boots = 0;

    public static function reset(): void
    {
        self::$calls = [];
        self::$requests = [];
        self::$terminated = [];
        self::$boots = 0;
    }

    #[EventListener(priority: 10)]
    public function first(ThingHappened $event): void
    {
        self::$calls[] = 'first:' . $event->name;
    }

    #[EventListener(priority: 1)]
    public function second(ThingHappened $event): void
    {
        self::$calls[] = 'second:' . $event->name;
    }

    #[EventListener]
    public function request(KernelRequestEvent $event): void
    {
        self::$requests[] = $event->request->getPathInfo();
    }

    #[EventListener]
    public function terminate(KernelTerminateEvent $event): void
    {
        self::$terminated[] = $event->response->getStatusCode();
    }

    #[EventListener]
    public function boot(KernelBootEvent $event): void
    {
        self::$boots++;
    }
}
