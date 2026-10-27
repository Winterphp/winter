<?php

declare(strict_types=1);

namespace Winter;

use Symfony\Component\HttpFoundation\Request;
use Winter\Cli\CliApplication;
use Winter\Config\Config;
use Winter\Kernel\Kernel;

final class Application
{
    public static function run(string $rootDir, ?Config $config = null): void
    {
        $kernel = Kernel::boot($rootDir, $config);
        $request = Request::createFromGlobals();
        $response = $kernel->handle($request);
        $response->send();
        $kernel->terminate($request, $response);
    }

    public static function cli(string $rootDir): int
    {
        return (new CliApplication(Kernel::boot($rootDir)))->run();
    }
}
