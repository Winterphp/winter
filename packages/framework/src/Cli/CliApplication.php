<?php

declare(strict_types=1);

namespace Winter\Cli;

use Doctrine\Migrations\Tools\Console\Command\DiffCommand;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\Migrations\Tools\Console\Command\StatusCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Application;
use Winter\Cli\Command\CacheClearCommand;
use Winter\Cli\Command\CacheCompileCommand;
use Winter\Cli\Command\DebugConfigCommand;
use Winter\Cli\Command\DebugContainerCommand;
use Winter\Cli\Command\MakeEntityCommand;
use Winter\Cli\Command\RouteListCommand;
use Winter\Data\MigrationFactory;
use Winter\Kernel\Kernel;

final class CliApplication
{
    public function __construct(private readonly Kernel $kernel)
    {
    }

    public function run(): int
    {
        $application = new Application('Winter', '0.1.0');

        $application->addCommands([
            new CacheCompileCommand($this->kernel),
            new CacheClearCommand($this->kernel),
            new RouteListCommand($this->kernel),
            new DebugContainerCommand($this->kernel),
            new DebugConfigCommand($this->kernel),
            new MakeEntityCommand($this->kernel),
        ]);

        $this->registerMigrationsCommands($application);

        return $application->run();
    }

    private function registerMigrationsCommands(Application $application): void
    {
        try {
            $manager = $this->kernel->container()->get(EntityManagerInterface::class);

            if (!$manager instanceof EntityManagerInterface) {
                return;
            }

            $factory = MigrationFactory::dependencyFactory(
                $this->kernel->config(),
                $this->kernel->paths(),
                $manager,
            );

            $migrate = new MigrateCommand($factory);
            $migrate->setAliases(['db:migrate']);

            $diff = new DiffCommand($factory);
            $diff->setAliases(['db:diff']);

            $status = new StatusCommand($factory);
            $status->setAliases(['db:status']);

            $application->addCommands([$migrate, $diff, $status]);
        } catch (\Throwable) {
            // Pas de DATABASE_URL : les commandes de migration ne sont pas disponibles.
        }
    }
}
