<?php

declare(strict_types=1);

namespace Winter\Data;

use Doctrine\ORM\EntityManagerInterface;
use Winter\Security\UserInterface;
use Winter\Security\UserProviderInterface;

final class DoctrineUserProvider implements UserProviderInterface
{
    /**
     * @param class-string $entityClass
     */
    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly string $entityClass,
        private readonly string $identifierField,
    ) {
    }

    public function loadByIdentifier(string $identifier): ?UserInterface
    {
        $entity = $this->manager->getRepository($this->entityClass)->findOneBy([
            $this->identifierField => $identifier,
        ]);

        if ($entity === null) {
            return null;
        }

        if (!$entity instanceof UserInterface) {
            throw new \RuntimeException(sprintf(
                'Security user entity "%s" must implement %s.',
                $this->entityClass,
                UserInterface::class,
            ));
        }

        return $entity;
    }
}
