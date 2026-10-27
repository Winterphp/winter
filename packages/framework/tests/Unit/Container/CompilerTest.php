<?php

declare(strict_types=1);

use Winter\Config\Config;
use Winter\Container\Compiler;
use Winter\Container\ContainerBuilder;
use Winter\Tests\Fixtures\App\Controller\GreetingController;
use Winter\Tests\Fixtures\App\Formatter\GreetingFormatter;
use Winter\Tests\Fixtures\App\Service\GreetingService;

function fixtureManifest(): \Winter\Container\Manifest
{
    return (new Compiler())->compile([__DIR__ . '/../../Fixtures/App/src']);
}

test('le compilateur découvre stéréotypes, alias, beans et routes', function (): void {
    $manifest = fixtureManifest();

    expect($manifest->services)->toHaveKey(GreetingService::class)
        ->and($manifest->services)->toHaveKey(GreetingController::class)
        ->and($manifest->aliases)->toHaveKey(GreetingFormatter::class)
        ->and($manifest->aliases[GreetingFormatter::class])->toBe(\Winter\Tests\Fixtures\App\Formatter\SimpleGreetingFormatter::class)
        ->and($manifest->beans)->toHaveKey(DateTimeImmutable::class);

    $routes = array_column($manifest->routes, null, 'name');

    expect($routes)->toHaveKey('greeting.greet')
        ->and($routes['greeting.greet']['path'])->toBe('/hello/{name}')
        ->and($routes['greeting.greet']['methods'])->toBe(['GET']);
});

test('le conteneur injecte par type, par interface, par valeur et par bean', function (): void {
    $config = new Config([
        'app' => ['name' => 'Winter'],
        'cache' => ['dir' => sys_get_temp_dir() . '/winter-test-cache'],
    ]);

    $container = (new ContainerBuilder($config))->build(fixtureManifest(), [
        \Doctrine\ORM\EntityManagerInterface::class => \DI\factory(static fn (): never => throw new \RuntimeException('non requis par ce test')),
    ]);
    $service = $container->instance(GreetingService::class, GreetingService::class);

    expect($service->greet())->toBe('Hello Winter')
        ->and($service->now()->format('Y-m-d'))->toBe('2026-01-01');
});

test('le compilateur découvre les avis d’exception et les écouteurs', function (): void {
    $manifest = fixtureManifest();

    expect($manifest->exceptionHandlers)->not->toBeEmpty()
        ->and($manifest->eventListeners)->not->toBeEmpty()
        ->and($manifest->services)->toHaveKey(\Winter\Tests\Fixtures\App\Advice\ApiExceptionAdvice::class)
        ->and($manifest->services)->toHaveKey(\Winter\Tests\Fixtures\App\Event\ListenerOnly::class);
});

test('un paramètre scalaire sans #[Value] échoue à la compilation du conteneur', function (): void {
    $manifest = new \Winter\Container\Manifest(
        services: [\Winter\Tests\Fixtures\Broken\NeedsScalar::class => ['name' => null, 'primary' => false]],
    );

    (new ContainerBuilder(new Config()))->build($manifest);
})->throws(\Winter\Container\Exception\ServiceNotFoundException::class);
