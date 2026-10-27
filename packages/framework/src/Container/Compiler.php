<?php

declare(strict_types=1);

namespace Winter\Container;

use Winter\Attribute\Bean;
use Winter\Attribute\Component;
use Winter\Attribute\Configuration;
use Winter\Attribute\ConfigurationProperties;
use Winter\Attribute\Controller;
use Winter\Attribute\ControllerAdvice;
use Winter\Attribute\EventListener;
use Winter\Attribute\ExceptionHandler;
use Winter\Attribute\Order;
use Winter\Attribute\Primary;
use Winter\Attribute\Profile;
use Winter\Attribute\ResponseStatus;
use Winter\Attribute\Route;
use Winter\Attribute\SecurityUser;
use Winter\Config\ConfigurationException;
use Winter\Support\Reflection;
use Winter\Support\Str;

final class Compiler
{
    public function __construct(private readonly ClassScanner $scanner = new ClassScanner())
    {
    }

    /**
     * @param list<string> $scanPaths
     * @param list<string> $profiles
     */
    public function compile(array $scanPaths, array $profiles = []): Manifest
    {
        $classes = $this->scanner->scan($scanPaths);

        /** @var array<string, array{name: ?string, primary: bool}> $services */
        $services = [];
        /** @var array<string, string> $aliases */
        $aliases = [];
        /** @var array<string, array{class: string, method: string, return: string}> $beans */
        $beans = [];
        /** @var array<string, list<string>> $implementations */
        $implementations = [];
        /** @var list<array{path: string, methods: list<string>, name: string, controller: string, action: string, produces: ?string}> $routes */
        $routes = [];
        /** @var array<string, string> $configProperties */
        $configProperties = [];
        /** @var array<string, string> $securityUsers */
        $securityUsers = [];
        /** @var array<string, string> $names */
        $names = [];

        // 1. Stéréotypes, avis d'exception et écouteurs d'événements
        foreach ($classes as $fqcn => $class) {
            if ($class->isAbstract() || $class->isInterface() || $class->isTrait() || $class->isEnum()) {
                continue;
            }

            $classProfile = Reflection::attribute($class, Profile::class);

            if ($classProfile instanceof Profile && !$classProfile->matches($profiles)) {
                continue;
            }

            // ConfigurationProperties et SecurityUser s'appliquent à toute classe, pas seulement aux stéréotypes.
            $properties = Reflection::attribute($class, ConfigurationProperties::class);
            if ($properties instanceof ConfigurationProperties) {
                $configProperties[$fqcn] = $properties->prefix;
            }

            $securityUser = Reflection::attribute($class, SecurityUser::class);
            if ($securityUser instanceof SecurityUser) {
                $securityUsers[$fqcn] = $securityUser->identifier;
            }

            $component = Reflection::attribute($class, Component::class);
            $advice = Reflection::attribute($class, ControllerAdvice::class);

            $handlerMethods = [];
            $listenerMethods = [];

            foreach ($class->getMethods() as $method) {
                if (Reflection::attribute($method, ExceptionHandler::class) !== null) {
                    $handlerMethods[] = $method;
                }

                if (Reflection::attribute($method, EventListener::class) !== null) {
                    $listenerMethods[] = $method;
                }
            }

            if ($handlerMethods !== [] && !$advice instanceof ControllerAdvice) {
                throw new ConfigurationException(sprintf(
                    '%s declares #[ExceptionHandler] methods but is not annotated with #[ControllerAdvice].',
                    $fqcn,
                ));
            }

            if (!$component instanceof Component && !$advice instanceof ControllerAdvice && $listenerMethods === []) {
                continue;
            }

            $name = $component instanceof Component ? $component->name : null;

            if ($name !== null) {
                if (isset($names[$name])) {
                    throw new ConfigurationException(sprintf(
                        'Service name "%s" is declared twice (%s and %s).',
                        $name,
                        $names[$name],
                        $fqcn,
                    ));
                }

                $names[$name] = $fqcn;
            }

            $services[$fqcn] = [
                'name' => $name,
                'primary' => Reflection::attribute($class, Primary::class) !== null,
            ];

            foreach ($class->getInterfaceNames() as $interface) {
                if (isset($classes[$interface])) {
                    $implementations[$interface][] = $fqcn;
                }
            }
        }

        // 2. Alias d'interfaces (unique implémentation ou #[Primary])
        foreach ($implementations as $interface => $candidates) {
            $candidates = array_values(array_unique($candidates));

            if (\count($candidates) === 1) {
                $aliases[$interface] = $candidates[0];

                continue;
            }

            $primaries = array_values(array_filter(
                $candidates,
                static fn (string $class): bool => $services[$class]['primary'] ?? false,
            ));

            if (\count($primaries) === 1) {
                $aliases[$interface] = $primaries[0];

                continue;
            }

            throw new ConfigurationException(sprintf(
                "Interface %s has %d implementations (%s) and no single #[Primary].\n"
                . 'Use #[Primary] on one implementation or #[Qualifier(\'name\')] at the injection point.',
                $interface,
                \count($candidates),
                implode(', ', $candidates),
            ));
        }

        // 3. Beans
        foreach ($services as $fqcn => $definition) {
            $class = $classes[$fqcn];

            if (Reflection::attribute($class, Configuration::class) === null) {
                continue;
            }

            foreach ($class->getMethods() as $method) {
                $bean = Reflection::attribute($method, Bean::class);

                if (!$bean instanceof Bean) {
                    continue;
                }

                $methodProfile = Reflection::attribute($method, Profile::class);

                if ($methodProfile instanceof Profile && !$methodProfile->matches($profiles)) {
                    continue;
                }

                $returnType = $method->getReturnType();

                if (!$returnType instanceof \ReflectionNamedType || $returnType->isBuiltin()) {
                    throw new ConfigurationException(sprintf(
                        'Bean method %s::%s() must declare a class return type.',
                        $fqcn,
                        $method->getName(),
                    ));
                }

                $return = $returnType->getName();
                $id = $bean->name ?? $return;

                if (isset($beans[$id]) || isset($services[$id])) {
                    throw new ConfigurationException(sprintf('Bean "%s" is declared twice.', $id));
                }

                $beans[$id] = ['class' => $fqcn, 'method' => $method->getName(), 'return' => $return];

                if ($id !== $return && !isset($aliases[$return]) && !isset($services[$return])) {
                    $aliases[$return] = $id;
                }
            }
        }

        // 4. Routes
        foreach ($services as $fqcn => $definition) {
            $class = $classes[$fqcn];

            if (Reflection::attribute($class, Controller::class) === null) {
                continue;
            }

            $prefix = '';
            $classRoute = Reflection::attribute($class, Route::class);

            if ($classRoute instanceof Route) {
                $prefix = $classRoute->path;
            }

            foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->isStatic() || $method->isConstructor()) {
                    continue;
                }

                foreach (Reflection::attributes($method, Route::class) as $route) {
                    if (!$route instanceof Route) {
                        continue;
                    }

                    $name = $route->name ?? Str::routeName($fqcn, $method->getName());

                    foreach ($routes as $existing) {
                        if ($existing['name'] === $name) {
                            throw new ConfigurationException(sprintf('Route name "%s" is declared twice.', $name));
                        }
                    }

                    $routes[] = [
                        'path' => $this->joinPaths($prefix, $route->path),
                        'methods' => $route->methods,
                        'name' => $name,
                        'controller' => $fqcn,
                        'action' => $method->getName(),
                        'produces' => $route->produces,
                    ];
                }
            }
        }

        // 5. Avis d'exception et écouteurs d'événements
        $exceptionHandlers = [];
        $eventListeners = [];
        $seenHandlers = [];

        foreach (array_keys($services) as $fqcn) {
            $class = $classes[$fqcn];
            $classOrder = Reflection::attribute($class, Order::class);
            $defaultOrder = $classOrder instanceof Order ? $classOrder->value : 0;

            if (Reflection::attribute($class, ControllerAdvice::class) !== null) {
                foreach ($class->getMethods() as $method) {
                    $handler = Reflection::attribute($method, ExceptionHandler::class);

                    if (!$handler instanceof ExceptionHandler) {
                        continue;
                    }

                    if (!$method->isPublic() || $method->isStatic()) {
                        throw new ConfigurationException(sprintf(
                            '#[ExceptionHandler] method %s::%s() must be public and non-static.',
                            $fqcn,
                            $method->getName(),
                        ));
                    }

                    $exception = $handler->exception;

                    if ((!class_exists($exception) && !interface_exists($exception)) || !is_a($exception, \Throwable::class, true)) {
                        throw new ConfigurationException(sprintf(
                            '#[ExceptionHandler] on %s::%s() references "%s", which is not a Throwable class.',
                            $fqcn,
                            $method->getName(),
                            $exception,
                        ));
                    }

                    $key = $fqcn . '|' . $exception;

                    if (isset($seenHandlers[$key])) {
                        throw new ConfigurationException(sprintf(
                            '%s declares two #[ExceptionHandler] methods for %s.',
                            $fqcn,
                            $exception,
                        ));
                    }

                    $seenHandlers[$key] = true;

                    $methodOrder = Reflection::attribute($method, Order::class);
                    $status = Reflection::attribute($method, ResponseStatus::class);

                    $exceptionHandlers[] = [
                        'class' => $fqcn,
                        'method' => $method->getName(),
                        'exception' => $exception,
                        'order' => $methodOrder instanceof Order ? $methodOrder->value : $defaultOrder,
                        'status' => $status instanceof ResponseStatus ? $status->status : null,
                    ];
                }
            }

            foreach ($class->getMethods() as $method) {
                $listener = Reflection::attribute($method, EventListener::class);

                if (!$listener instanceof EventListener) {
                    continue;
                }

                if (!$method->isPublic() || $method->isStatic()) {
                    throw new ConfigurationException(sprintf(
                        '#[EventListener] method %s::%s() must be public and non-static.',
                        $fqcn,
                        $method->getName(),
                    ));
                }

                $parameters = $method->getParameters();

                if (\count($parameters) !== 1) {
                    throw new ConfigurationException(sprintf(
                        '#[EventListener] method %s::%s() must declare exactly one event parameter.',
                        $fqcn,
                        $method->getName(),
                    ));
                }

                $event = Reflection::typeName($parameters[0]);

                if ($event === null || (!class_exists($event) && !interface_exists($event))) {
                    throw new ConfigurationException(sprintf(
                        '#[EventListener] method %s::%s() must type its parameter with an event class.',
                        $fqcn,
                        $method->getName(),
                    ));
                }

                $eventListeners[] = [
                    'class' => $fqcn,
                    'method' => $method->getName(),
                    'event' => $event,
                    'priority' => $listener->priority,
                ];
            }
        }

        if (\count($securityUsers) > 1) {
            throw new ConfigurationException(
                'Several #[SecurityUser] classes found (' . implode(', ', array_keys($securityUsers)) . '). Only one is supported.',
            );
        }

        return new Manifest(
            services: $services,
            aliases: $aliases,
            beans: $beans,
            routes: $routes,
            configProperties: $configProperties,
            securityUsers: $securityUsers,
            scanPaths: $scanPaths,
            compiledAt: time(),
            profiles: $profiles,
            exceptionHandlers: $exceptionHandlers,
            eventListeners: $eventListeners,
        );
    }

    public function write(Manifest $manifest, string $file): void
    {
        $directory = \dirname($file);

        if (!is_dir($directory) && !mkdir($directory, 0o775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create cache directory "%s".', $directory));
        }

        $php = "<?php\n\n// Généré par Winter. Ne pas éditer.\n\nreturn " . var_export($manifest->toArray(), true) . ";\n";
        $temporary = $file . '.tmp';

        if (file_put_contents($temporary, $php) === false || !rename($temporary, $file)) {
            throw new \RuntimeException(sprintf('Unable to write manifest cache "%s".', $file));
        }
    }

    private function joinPaths(string $prefix, string $path): string
    {
        $path = '/' . trim($prefix, '/') . '/' . trim($path, '/');
        $path = rtrim((string) preg_replace('#/+#', '/', $path), '/');

        return $path === '' ? '/' : $path;
    }
}
