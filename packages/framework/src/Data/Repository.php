<?php

declare(strict_types=1);

namespace Winter\Data;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

/**
 * Repository de base. L'entité gérée est déclarée par entityClass().
 */
abstract class Repository
{
    public function __construct(protected readonly EntityManagerInterface $manager)
    {
    }

    /**
     * @return class-string
     */
    abstract protected function entityClass(): string;

    public function find(mixed $id): ?object
    {
        return $this->repository()->find($id);
    }

    /**
     * @return list<object>
     */
    public function findAll(?array $orderBy = null): array
    {
        return $this->repository()->findBy([], $orderBy);
    }

    /**
     * @param array<string, mixed>      $criteria
     * @param array<string, string>|null $orderBy
     *
     * @return list<object>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        return $this->repository()->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * @param array<string, mixed> $criteria
     */
    public function findOneBy(array $criteria): ?object
    {
        return $this->repository()->findOneBy($criteria);
    }

    public function save(object $entity): void
    {
        $this->manager->persist($entity);
        $this->manager->flush();
    }

    public function remove(object $entity): void
    {
        $this->manager->remove($entity);
        $this->manager->flush();
    }

    public function flush(): void
    {
        $this->manager->flush();
    }

    /**
     * @param array<string, mixed>  $criteria
     * @param array<string, string> $orderBy
     */
    public function paginate(int $page = 1, int $perPage = 20, array $criteria = [], array $orderBy = []): Pagination
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $class = $this->entityClass();

        $query = $this->manager->createQueryBuilder()->select('e')->from($class, 'e');

        foreach ($criteria as $field => $value) {
            $query->andWhere(sprintf('e.%s = :%s', $field, $field))->setParameter($field, $value);
        }

        $count = clone $query;
        $count->select(sprintf('COUNT(e)'))->resetDQLPart('orderBy');
        $total = (int) $count->getQuery()->getSingleScalarResult();

        foreach ($orderBy as $field => $direction) {
            $query->addOrderBy(sprintf('e.%s', $field), strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC');
        }

        /** @var list<object> $items */
        $items = $query
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return new Pagination($items, $total, $page, $perPage);
    }

    protected function repository(): EntityRepository
    {
        return $this->manager->getRepository($this->entityClass());
    }
}
