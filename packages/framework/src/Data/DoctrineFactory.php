<?php

declare(strict_types=1);

namespace Winter\Data;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\ORM\Configuration as OrmConfiguration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Proxy\ProxyFactory;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Winter\Config\Config;
use Winter\Config\ConfigurationException;
use Winter\Support\Paths;

final class DoctrineFactory
{
    public static function create(Config $config, Paths $paths): EntityManagerInterface
    {
        $url = $config->string('database.url');

        if ($url === null || $url === '') {
            throw new ConfigurationException(
                'DATABASE_URL is not configured. Set it in .env, or define config/database.php.',
            );
        }

        $debug = $config->bool('app.debug', false);

        /** @var list<string> $entityPaths */
        $entityPaths = $config->get('database.paths', [$paths->src()]);
        $entityPaths = array_values(array_filter($entityPaths, static fn (mixed $path): bool => \is_string($path)));

        $orm = new OrmConfiguration();
        $orm->setMetadataDriverImpl(new AttributeDriver($entityPaths));

        $cacheDirectory = $paths->ensure((string) $config->string('database.cache_dir', $paths->cache() . '/doctrine'));

        if (PHP_VERSION_ID >= 80400) {
            // PHP 8.4 : objets paresseux natifs, aucun dossier de proxies à configurer.
            $orm->enableNativeLazyObjects(true);
        } else {
            // PHP 8.3 : Doctrine exige un dossier de proxies, sinon l'EntityManager
            // échoue à la première instanciation (ORMInvalidArgumentException).
            $orm->setProxyDir($cacheDirectory . '/proxies');
            $orm->setProxyNamespace('Winter\\Proxies');
            $orm->setAutoGenerateProxyClasses(
                $debug ? ProxyFactory::AUTOGENERATE_ALWAYS : ProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS,
            );
        }

        $cache = $debug
            ? new ArrayAdapter()
            : new FilesystemAdapter('winter_doctrine', 0, $cacheDirectory . '/metadata');

        $orm->setMetadataCache($cache);
        $orm->setQueryCache($cache);

        $parameters = (new DsnParser())->parse($url);
        $parameters['driver'] = self::driverName((string) ($parameters['driver'] ?? ''));

        $connection = DriverManager::getConnection($parameters, $orm);

        return new EntityManager($connection, $orm);
    }

    /**
     * DBAL 4 attend les noms de drivers PDO explicites.
     */
    private static function driverName(string $driver): string
    {
        return match ($driver) {
            'mysql' => 'pdo_mysql',
            'pgsql', 'postgres', 'postgresql' => 'pdo_pgsql',
            'sqlite' => 'pdo_sqlite',
            'sqlsrv', 'mssql' => 'pdo_sqlsrv',
            'oci' => 'pdo_oci',
            default => $driver,
        };
    }
}
