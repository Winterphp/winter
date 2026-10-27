<?php

declare(strict_types=1);

namespace Winter\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Winter\Kernel\Kernel;

final class CacheClearCommand extends Command
{
    public function __construct(private readonly Kernel $kernel)
    {
        parent::__construct('cache:clear');
    }

    protected function configure(): void
    {
        $this->setDescription('Vide var/cache (manifeste, container compilé, routes, Twig, Doctrine).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->kernel->clearCache();
        (new SymfonyStyle($input, $output))->success('Cache vidé : ' . $this->kernel->paths()->cache());

        return Command::SUCCESS;
    }
}
