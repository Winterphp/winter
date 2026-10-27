<?php

declare(strict_types=1);

namespace Winter\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Winter\Kernel\Kernel;

final class CacheCompileCommand extends Command
{
    public function __construct(private readonly Kernel $kernel)
    {
        parent::__construct('cache:compile');
    }

    protected function configure(): void
    {
        $this->setDescription('Scanne src/, valide les dépendances et compile le container, les routes et Twig.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $result = $this->kernel->compile();

        $io->success(sprintf(
            '%d services, %d routes compilés dans %s (profils : %s)',
            $result['services'],
            $result['routes'],
            $result['cache'],
            implode(', ', $this->kernel->config()->profiles()),
        ));

        return Command::SUCCESS;
    }
}
