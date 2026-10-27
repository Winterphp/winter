<?php

declare(strict_types=1);

$autoloads = [
    \dirname(__DIR__) . '/vendor/autoload.php',        // installation autonome
    \dirname(__DIR__, 3) . '/vendor/autoload.php',     // monorepo Winter
];

foreach ($autoloads as $autoload) {
    if (is_file($autoload)) {
        require $autoload;

        break;
    }
}

\Winter\Application::run(\dirname(__DIR__));
