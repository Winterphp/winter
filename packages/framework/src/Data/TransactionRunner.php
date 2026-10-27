<?php

declare(strict_types=1);

namespace Winter\Data;

use Doctrine\ORM\EntityManagerInterface;

final class TransactionRunner
{
    public function __construct(private readonly EntityManagerInterface $manager)
    {
    }

    /**
     * Exécute $callback dans une transaction, flush puis commit ; rollback en cas d'erreur.
     *
     * @template T
     *
     * @param callable(EntityManagerInterface): T $callback
     *
     * @return T
     */
    public function run(callable $callback): mixed
    {
        $this->manager->beginTransaction();

        try {
            $result = $callback($this->manager);
            $this->manager->flush();
            $this->manager->commit();

            return $result;
        } catch (\Throwable $exception) {
            $this->manager->rollback();

            throw $exception;
        }
    }
}
