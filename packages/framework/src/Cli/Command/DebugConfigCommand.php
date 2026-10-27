<?php

declare(strict_types=1);

namespace Winter\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Winter\Kernel\Kernel;

final class DebugConfigCommand extends Command
{
    public function __construct(private readonly Kernel $kernel)
    {
        parent::__construct('debug:config');
    }

    protected function configure(): void
    {
        $this->setDescription('Affiche la configuration résolue (secrets masqués).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln($this->dump($this->kernel->config()->all()));

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $values
     */
    private function dump(array $values, int $depth = 0): string
    {
        $lines = '';

        foreach ($values as $key => $value) {
            $label = str_repeat('  ', $depth) . $key;

            if (\is_array($value)) {
                $lines .= $label . ":\n" . $this->dump($value, $depth + 1);

                continue;
            }

            if (\is_string($key) && preg_match('/secret|password|token|dsn|url/i', $key) === 1 && $value !== null && $value !== '') {
                $value = '********';
            }

            $lines .= $label . ': ' . var_export($value, true) . "\n";
        }

        return $lines;
    }
}
