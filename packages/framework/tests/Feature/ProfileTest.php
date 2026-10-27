<?php

declare(strict_types=1);

use Winter\Config\Config;
use Winter\Container\Compiler;
use Winter\Kernel\Kernel;
use Winter\Tests\Fixtures\App\Controller\ProdOnlyController;
use Winter\Tests\Fixtures\App\Service\ProdOnlyService;
use Winter\Tests\Fixtures\App\Service\ProfiledBean;
use Winter\Tests\Fixtures\App\Service\TestOnlyService;

function fixtureSource(): string
{
    return \dirname(__DIR__) . '/Fixtures/App/src';
}

test('le compilateur filtre services et routes selon le profil actif', function (): void {
    $compiler = new Compiler();

    $test = $compiler->compile([fixtureSource()], ['test']);
    $prod = $compiler->compile([fixtureSource()], ['prod']);

    expect($test->services)->toHaveKey(TestOnlyService::class)
        ->and($test->services)->not->toHaveKey(ProdOnlyService::class)
        ->and($test->services)->not->toHaveKey(ProdOnlyController::class)
        ->and(array_column($test->routes, 'path'))->not->toContain('/prod-only')
        ->and($prod->services)->toHaveKey(ProdOnlyService::class)
        ->and($prod->services)->not->toHaveKey(TestOnlyService::class)
        ->and(array_column($prod->routes, 'path'))->toContain('/prod-only')
        ->and($test->beans)->toHaveKey(ProfiledBean::class)
        ->and($prod->beans)->toHaveKey(ProfiledBean::class);
});

test('les fichiers config/{clé}.{profil}.php surchargent la configuration de base', function (): void {
    $root = \dirname(__DIR__) . '/Fixtures/App';

    $test = Config::fromRoot($root, ['app' => ['env' => 'test', 'profiles' => []]]);
    $prod = Config::fromRoot($root, ['app' => ['env' => 'prod', 'profiles' => []]]);

    expect($test->get('feature.flag'))->toBe('test')
        ->and($test->profiles())->toBe(['test'])
        ->and($prod->get('feature.flag'))->toBe('base')
        ->and($prod->profiles())->toBe(['prod']);
});

test('un changement de profil invalide le manifeste compilé', function (): void {
    $root = \dirname(__DIR__) . '/Fixtures/App';
    $cache = sys_get_temp_dir() . '/winter-profile-' . bin2hex(random_bytes(6));

    $config = static fn (string $env): Config => Config::fromRoot($root, [
        'app' => ['env' => $env, 'profiles' => [], 'debug' => true],
        'security' => ['session_storage' => 'array'],
        'logging' => ['level' => 'error'],
    ]);

    $testKernel = Kernel::boot($root, $config('test'), ['cache_dir' => $cache]);
    $testPaths = array_column($testKernel->router()->list(), 'path');

    $prodKernel = Kernel::boot($root, $config('prod'), ['cache_dir' => $cache]);
    $prodPaths = array_column($prodKernel->router()->list(), 'path');

    expect($testKernel->manifest()->profiles)->toBe(['test'])
        ->and($testPaths)->not->toContain('/prod-only')
        ->and($prodKernel->manifest()->profiles)->toBe(['prod'])
        ->and($prodPaths)->toContain('/prod-only');

    $prodKernel->clearCache();
});
