<?php

declare(strict_types=1);

namespace Winter\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Winter\Kernel\Kernel;

final class RouteListCommand extends Command
{
    public function __construct(private readonly Kernel $kernel)
    {
        parent::__construct('route:list');
    }

    protected function configure(): void
    {
        $this->setDescription('Liste les routes déclarées par attributs.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = new Table($output);
        $table->setHeaders(['Méthode', 'Path', 'Nom', 'Contrôleur']);

        foreach ($this->kernel->router()->list() as $route) {
            $table->addRow([
                $route['method'],
                $route['path'],
                $route['name'],
                $route['controller'] . '::' . $route['action'],
            ]);
        }

        $table->render();

        return Command::SUCCESS;
    }
}
