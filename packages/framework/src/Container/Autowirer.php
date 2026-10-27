<?php

declare(strict_types=1);

namespace Winter\Container;

use Winter\Attribute\Qualifier;
use Winter\Attribute\Value;
use Winter\Container\Exception\ServiceNotFoundException;
use Winter\Support\Reflection;

final class Autowirer
{
    /**
     * @param list<string>          $ids     identifiants connus du conteneur
     * @param array<string, string> $aliases interface => identifiant
     */
    public function __construct(
        private readonly array $ids,
        private readonly array $aliases = [],
    ) {
    }

    /**
     * @return list<array{kind: string, id?: string, key?: string, hasDefault?: bool, default?: mixed, value?: mixed}>
     */
    public function plan(\ReflectionMethod $method): array
    {
        $plan = [];

        foreach ($method->getParameters() as $parameter) {
            $plan[] = $this->resolveParameter($parameter);
        }

        return $plan;
    }

    /**
     * @return array{kind: string, id?: string, key?: string, hasDefault?: bool, default?: mixed, value?: mixed}
     */
    private function resolveParameter(\ReflectionParameter $parameter): array
    {
        $owner = $parameter->getDeclaringClass()?->getName() ?? 'unknown';

        $value = Reflection::attribute($parameter, Value::class);
        if ($value instanceof Value) {
            return [
                'kind' => 'value',
                'key' => $value->key,
                'hasDefault' => $value->hasDefault,
                'default' => $value->default,
            ];
        }

        $qualifier = Reflection::attribute($parameter, Qualifier::class);
        if ($qualifier instanceof Qualifier) {
            if (!\in_array($qualifier->name, $this->ids, true)) {
                throw new ServiceNotFoundException(sprintf(
                    'Qualifier "%s" used by %s::__construct($%s) matches no service.',
                    $qualifier->name,
                    $owner,
                    $parameter->getName(),
                ));
            }

            return ['kind' => 'service', 'id' => $qualifier->name];
        }

        $type = Reflection::typeName($parameter);

        if ($type !== null) {
            $isAbstract = interface_exists($type) || (class_exists($type) && (new \ReflectionClass($type))->isAbstract());

            if ($isAbstract) {
                $id = $this->aliases[$type] ?? (\in_array($type, $this->ids, true) ? $type : null);

                if ($id === null) {
                    throw new ServiceNotFoundException(sprintf(
                        'No implementation registered for %s required by %s::__construct($%s). '
                        . 'Add #[Component] to an implementation, or #[Primary]/#[Qualifier].',
                        $type,
                        $owner,
                        $parameter->getName(),
                    ));
                }

                return ['kind' => 'service', 'id' => $id];
            }

            return ['kind' => 'service', 'id' => $type];
        }

        if ($parameter->isDefaultValueAvailable()) {
            return ['kind' => 'literal', 'value' => $parameter->getDefaultValue()];
        }

        if ($parameter->allowsNull()) {
            return ['kind' => 'literal', 'value' => null];
        }

        throw new ServiceNotFoundException(sprintf(
            'Cannot resolve scalar parameter $%s of %s. Declare it with #[Value(\'CONFIG_KEY\')] or give it a default value.',
            $parameter->getName(),
            $owner,
        ));
    }
}
