<?php

declare(strict_types=1);

namespace Winter\Tests\Fixtures\App\Event;

use Winter\Attribute\EventListener;

final class ListenerOnly
{
    #[EventListener]
    public function onThing(ThingHappened $event): void
    {
    }
}
