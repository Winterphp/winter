<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Request;
use Winter\Config\Config;
use Winter\Event\ApplicationEventPublisher;
use Winter\Kernel\Kernel;
use Winter\Tests\Fixtures\App\Event\RecordingListener;
use Winter\Tests\Fixtures\App\Event\ThingHappened;

function eventKernel(): Kernel
{
    $root = \dirname(__DIR__) . '/Fixtures/App';

    return Kernel::boot($root, Config::fromRoot($root, [
        'app' => ['env' => 'test', 'debug' => true],
        'security' => ['session_storage' => 'array'],
        'logging' => ['level' => 'error'],
    ]));
}

test('les écouteurs sont appelés par priorité décroissante', function (): void {
    $kernel = eventKernel();
    RecordingListener::reset();

    $publisher = $kernel->container()->get(ApplicationEventPublisher::class);
    $publisher->publish(new ThingHappened('demo'));

    expect(RecordingListener::$calls)->toBe(['first:demo', 'second:demo']);
});

test('l’événement de démarrage est publié', function (): void {
    RecordingListener::reset();
    eventKernel();

    expect(RecordingListener::$boots)->toBe(1);
});

test('les événements de requête et de fin de requête sont publiés', function (): void {
    $kernel = eventKernel();
    RecordingListener::reset();

    $request = Request::create('/hello/World');
    $response = $kernel->handle($request);
    $kernel->terminate($request, $response);

    expect(RecordingListener::$requests)->toBe(['/hello/World'])
        ->and(RecordingListener::$terminated)->toBe([200]);
});
