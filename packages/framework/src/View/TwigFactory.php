<?php

declare(strict_types=1);

namespace Winter\View;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;
use Winter\Config\Config;
use Winter\Http\RouteGenerator;
use Winter\Support\Paths;

final class TwigFactory
{
    public static function create(Config $config, Paths $paths, RouteGenerator $routes): Environment
    {
        $loader = new FilesystemLoader();

        foreach ($config->get('twig.paths', []) as $path) {
            if (is_string($path) && is_dir($path)) {
                $loader->addPath($path);
            }
        }

        $loader->addPath(\dirname(__DIR__) . '/resources', 'framework');

        $cache = $config->get('twig.cache', false);
        $environment = new Environment($loader, [
            'cache' => \is_string($cache) && $cache !== '' ? $paths->ensure($cache) : false,
            'debug' => $config->bool('app.debug', false),
            'autoescape' => 'html',
            'strict_variables' => false,
        ]);

        $environment->addFunction(new TwigFunction('path', static fn (string $name, array $parameters = []): string => $routes->path($name, $parameters)));

        return $environment;
    }
}
