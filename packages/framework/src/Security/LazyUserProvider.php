<?php

declare(strict_types=1);

namespace Winter\Security;

use Psr\Container\ContainerInterface;

final class LazyUserProvider implements UserProviderInterface
{
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    public function loadByIdentifier(string $identifier): ?UserInterface
    {
        $provider = $this->container->get('winter.user_provider.real');

        if (!$provider instanceof UserProviderInterface) {
            throw new \RuntimeException('winter.user_provider.real must implement ' . UserProviderInterface::class . '.');
        }

        return $provider->loadByIdentifier($identifier);
    }
}
