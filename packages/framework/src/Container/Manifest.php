<?php

declare(strict_types=1);

namespace Winter\Container;

final class Manifest
{
    /**
     * @param array<string, array{name: ?string, primary: bool}> $services
     * @param array<string, string>                              $aliases
     * @param array<string, array{class: string, method: string, return: string}> $beans
     * @param list<array{path: string, methods: list<string>, name: string, controller: string, action: string, produces: ?string}> $routes
     * @param array<string, string>                              $configProperties
     * @param array<string, string>                              $securityUsers
     * @param list<string>                                       $scanPaths
     * @param list<string>                                       $profiles
     * @param list<array{class: string, method: string, exception: string, order: int, status: ?int}> $exceptionHandlers
     * @param list<array{class: string, method: string, event: string, priority: int}> $eventListeners
     */
    public function __construct(
        public readonly array $services = [],
        public readonly array $aliases = [],
        public readonly array $beans = [],
        public readonly array $routes = [],
        public readonly array $configProperties = [],
        public readonly array $securityUsers = [],
        public readonly array $scanPaths = [],
        public readonly int $compiledAt = 0,
        public readonly array $profiles = [],
        public readonly array $exceptionHandlers = [],
        public readonly array $eventListeners = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            services: $data['services'] ?? [],
            aliases: $data['aliases'] ?? [],
            beans: $data['beans'] ?? [],
            routes: $data['routes'] ?? [],
            configProperties: $data['configProperties'] ?? [],
            securityUsers: $data['securityUsers'] ?? [],
            scanPaths: $data['scanPaths'] ?? [],
            compiledAt: (int) ($data['compiledAt'] ?? 0),
            profiles: $data['profiles'] ?? [],
            exceptionHandlers: $data['exceptionHandlers'] ?? [],
            eventListeners: $data['eventListeners'] ?? [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'services' => $this->services,
            'aliases' => $this->aliases,
            'beans' => $this->beans,
            'routes' => $this->routes,
            'configProperties' => $this->configProperties,
            'securityUsers' => $this->securityUsers,
            'scanPaths' => $this->scanPaths,
            'compiledAt' => $this->compiledAt,
            'profiles' => $this->profiles,
            'exceptionHandlers' => $this->exceptionHandlers,
            'eventListeners' => $this->eventListeners,
        ];
    }

    /**
     * @return list<string>
     */
    public function serviceIds(): array
    {
        return array_values(array_unique(array_merge(
            array_keys($this->services),
            array_keys($this->aliases),
            array_keys($this->beans),
            array_map(static fn (array $bean): string => $bean['return'], $this->beans),
        )));
    }

    /**
     * Nom de service qualifié → identifiant de conteneur.
     */
    public function idForName(string $name): ?string
    {
        foreach ($this->services as $class => $definition) {
            if ($definition['name'] === $name) {
                return $class;
            }
        }

        return \array_key_exists($name, $this->beans) ? $name : null;
    }
}
