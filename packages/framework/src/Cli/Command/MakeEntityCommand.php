<?php

declare(strict_types=1);

namespace Winter\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Winter\Kernel\Kernel;
use Winter\Support\Str;

final class MakeEntityCommand extends Command
{
    public function __construct(private readonly Kernel $kernel)
    {
        parent::__construct('make:entity');
    }

    protected function configure(): void
    {
        $this->setDescription('Génère une entité Doctrine minimale dans src/Entity.')
            ->addArgument('name', InputArgument::REQUIRED, 'Nom de l’entité (ex. Invoice)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = Str::studly((string) $input->getArgument('name'));
        $namespace = rtrim((string) $this->kernel->config()->string('app.namespace', 'App\\'), '\\');
        $directory = $this->kernel->paths()->src() . '/Entity';
        $file = $directory . '/' . $name . '.php';

        if (is_file($file)) {
            $io->error('Le fichier existe déjà : ' . $file);

            return Command::FAILURE;
        }

        if (!is_dir($directory) && !mkdir($directory, 0o775, true) && !is_dir($directory)) {
            $io->error('Impossible de créer ' . $directory);

            return Command::FAILURE;
        }

        $table = Str::snake($name);
        $code = <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Entity;

use Doctrine\\ORM\\Mapping as ORM;

#[ORM\\Entity]
#[ORM\\Table(name: '{$table}')]
class {$name}
{
    #[ORM\\Id]
    #[ORM\\GeneratedValue]
    #[ORM\\Column(type: 'integer')]
    private ?int \$id = null;

    public function getId(): ?int
    {
        return \$this->id;
    }
}
PHP;

        file_put_contents($file, $code . "\n");
        $io->success('Entité créée : ' . $file);

        return Command::SUCCESS;
    }
}
