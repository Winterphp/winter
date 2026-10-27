<?php

declare(strict_types=1);

namespace Winter\Logging;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Winter\Config\Config;
use Winter\Support\Paths;

final class LoggerFactory
{
    public static function create(Config $config, Paths $paths): LoggerInterface
    {
        $logger = new Logger('winter');
        $level = $config->string('logging.level', 'debug') ?? 'debug';

        $directory = $paths->ensure($paths->log());
        $logger->pushHandler(new StreamHandler($directory . '/app.log', $level));

        if ($config->bool('logging.stderr', false)) {
            $logger->pushHandler(new StreamHandler('php://stderr', $level));
        }

        return $logger;
    }
}
