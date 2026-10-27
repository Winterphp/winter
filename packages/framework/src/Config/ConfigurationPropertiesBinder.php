<?php

declare(strict_types=1);

namespace Winter\Config;

use Winter\Attribute\ConfigurationProperties;
use Winter\Support\Reflection;

final class ConfigurationPropertiesBinder
{
    public function bind(Config $config, string $class): object
    {
        $reflector = new \ReflectionClass($class);
        $attribute = Reflection::attribute($reflector, ConfigurationProperties::class);

        if (!$attribute instanceof ConfigurationProperties) {
            throw new ConfigurationException(sprintf('Class "%s" is not annotated with #[ConfigurationProperties].', $class));
        }

        $values = $config->get($attribute->prefix, []);

        if (!\is_array($values)) {
            throw new ConfigurationException(sprintf('Configuration key "%s" must be an array.', $attribute->prefix));
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
            $instance = $reflector->newInstance();
            $this->hydrateProperties($reflector, $instance, $values, $attribute->prefix);

            return $instance;
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (\array_key_exists($name, $values)) {
                $arguments[] = $values[$name];

                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();

                continue;
            }

            if ($parameter->allowsNull()) {
                $arguments[] = null;

                continue;
            }

            throw new ConfigurationException(sprintf(
                'Missing configuration key "%s.%s" required by "%s".',
                $attribute->prefix,
                $name,
                $class,
            ));
        }

        return $reflector->newInstanceArgs($arguments);
    }

    /**
     * @param array<string, mixed> $values
     */
    private function hydrateProperties(\ReflectionClass $reflector, object $instance, array $values, string $prefix): void
    {
        foreach ($reflector->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isReadOnly() || !\array_key_exists($property->getName(), $values)) {
                continue;
            }

            $property->setValue($instance, $values[$property->getName()]);
        }
    }
}
