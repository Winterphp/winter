<?php

declare(strict_types=1);

namespace Winter\Data;

use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\ORM\EntityManagerInterface;
use Winter\Config\Config;
use Winter\Support\Paths;

final class MigrationFactory
{
    public static function dependencyFactory(Config $config, Paths $paths, EntityManagerInterface $manager): DependencyFactory
    {
        $directory = (string) $config->string('database.migrations_path', $paths->root . '/migrations');
        $namespace = rtrim((string) $config->string('app.namespace', 'App\\'), '\\') . '\\Migrations';

        $paths->ensure($directory);

        $loader = new ConfigurationArray([
            'migrations_paths' => [$namespace => $directory],
            'all_or_nothing' => true,
            'transactional' => true,
        ]);

        return DependencyFactory::fromEntityManager($loader, new ExistingEntityManager($manager));
    }
}
