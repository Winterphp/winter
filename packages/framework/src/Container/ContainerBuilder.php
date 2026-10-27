<?php

declare(strict_types=1);

namespace Winter\Container;

use DI\ContainerBuilder as PhpDiBuilder;
use Winter\Attribute\Value;
use Winter\Config\Config;
use Winter\Config\ConfigurationException;
use Winter\Support\Reflection;

final class ContainerBuilder
{
    public function __construct(private readonly Config $config)
    {
    }

    /**
     * @param array<string, mixed> $extraDefinitions services framework (Config, logger, EntityManager…)
     */
    public function build(
        Manifest $manifest,
        array $extraDefinitions = [],
        bool $compile = false,
        ?string $compileDir = null,
        ?string $containerClass = null,
    ): Container {
        $definitions = $extraDefinitions;
        $ids = array_values(array_unique(array_merge(
            array_keys($definitions),
            $manifest->serviceIds(),
            array_keys($manifest->aliases),
            array_values($manifest->aliases),
        )));

        $autowirer = new Autowirer($ids, $manifest->aliases);

        foreach ($manifest->services as $class => $definition) {
            $reflection = new \ReflectionClass($class);
            $service = \DI\create($class);

            $constructor = $reflection->getConstructor();

            if ($constructor !== null && $constructor->getNumberOfParameters() > 0) {
                $arguments = [];

                foreach ($autowirer->plan($constructor) as $parameter) {
                    $arguments[] = $this->argument($parameter);
                }

                $service = $service->constructor(...$arguments);
            }

            $definitions[$class] = $service;

            if ($definition['name'] !== null) {
                $definitions[$definition['name']] = \DI\get($class);
            }
        }

        foreach ($manifest->beans as $id => $bean) {
            $method = new \ReflectionMethod($bean['class'], $bean['method']);
            $factory = \DI\factory([$bean['class'], $bean['method']]);
            $plan = $autowirer->plan($method);
            $parameters = $method->getParameters();

            foreach ($plan as $index => $parameter) {
                $factory = $factory->parameter($parameters[$index]->getName(), $this->argument($parameter));
            }

            $definitions[$id] = $factory;
        }

        foreach ($manifest->aliases as $interface => $target) {
            $definitions[$interface] = \DI\get($target);
        }

        $builder = new PhpDiBuilder();
        $builder->addDefinitions($definitions);

        if ($compile) {
            $directory = $compileDir ?? $this->config->string('cache.dir', sys_get_temp_dir()) . '/php-di';

            if (!is_dir($directory) && !mkdir($directory, 0o775, true) && !is_dir($directory)) {
                throw new \RuntimeException(sprintf('Unable to create compiled container directory "%s".', $directory));
            }

            $builder->enableCompilation(
                $directory,
                $containerClass ?? 'CompiledContainer_' . substr(md5($directory), 0, 8),
            );
        }

        return new Container($builder->build(), $manifest, $definitions);
    }

    /**
     * @param array{kind: string, id?: string, key?: string, hasDefault?: bool, default?: mixed, value?: mixed} $parameter
     */
    private function argument(array $parameter): mixed
    {
        return match ($parameter['kind']) {
            'service' => \DI\get((string) $parameter['id']),
            'literal' => $parameter['value'] ?? null,
            'value' => $this->configValue($parameter),
            default => throw new ConfigurationException(sprintf('Unknown parameter kind "%s".', $parameter['kind'])),
        };
    }

    /**
     * @param array{key?: string, hasDefault?: bool, default?: mixed} $parameter
     */
    private function configValue(array $parameter): mixed
    {
        $key = (string) ($parameter['key'] ?? '');

        if ($this->config->has($key)) {
            return $this->config->get($key);
        }

        if (($parameter['hasDefault'] ?? false) === true) {
            return $parameter['default'] ?? null;
        }

        throw new ConfigurationException(sprintf(
            'Configuration key "%s" is missing and has no default value.',
            $key,
        ));
    }
}
