<?php

declare(strict_types=1);

namespace Winter\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Winter\Kernel\Kernel;

final class DebugContainerCommand extends Command
{
    public function __construct(private readonly Kernel $kernel)
    {
        parent::__construct('debug:container');
    }

    protected function configure(): void
    {
        $this->setDescription('Liste les services, alias d’interfaces et beans du container.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $manifest = $this->kernel->manifest();
        $table = new Table($output);
        $table->setHeaders(['Service', 'Nom qualifié', 'Primary']);

        foreach ($manifest->services as $class => $definition) {
            $table->addRow([$class, $definition['name'] ?? '—', $definition['primary'] ? 'oui' : 'non']);
        }

        foreach ($manifest->beans as $id => $bean) {
            $table->addRow(['bean:' . $id, $bean['class'] . '::' . $bean['method'] . '()', '—']);
        }

        $table->render();

        if ($manifest->aliases !== []) {
            $output->writeln("\n<info>Alias d’interfaces</info>");

            foreach ($manifest->aliases as $interface => $target) {
                $output->writeln(sprintf('  %s → %s', $interface, $target));
            }
        }

        return Command::SUCCESS;
    }
}
