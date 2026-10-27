<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Winter\Config\Config;
use Winter\Data\TransactionRunner;
use Winter\Kernel\Kernel;
use Winter\Tests\Fixtures\App\Entity\Note;
use Winter\Tests\Fixtures\App\Repository\NoteRepositoryInterface;

function doctrineKernel(): Kernel
{
    $root = \dirname(__DIR__) . '/Fixtures/App';

    return Kernel::boot($root, Config::fromRoot($root, [
        'app' => ['env' => 'test', 'debug' => true],
        'security' => ['session_storage' => 'array'],
        'database' => ['url' => 'sqlite:///:memory:'],
        'logging' => ['level' => 'error'],
    ]));
}

function doctrineSchema(Kernel $kernel): void
{
    $manager = $kernel->container()->get(EntityManagerInterface::class);
    $tool = new SchemaTool($manager);
    $tool->createSchema([$manager->getClassMetadata(Note::class)]);
}

test('un repository injecté par son interface persiste et relit des entités', function (): void {
    $kernel = doctrineKernel();
    doctrineSchema($kernel);

    /** @var NoteRepositoryInterface $repository */
    $repository = $kernel->container()->get(NoteRepositoryInterface::class);
    $repository->create('Première');
    $repository->create('Seconde');

    $notes = $repository->all();

    expect($notes)->toHaveCount(2)
        ->and($notes[0]->getTitle())->toBe('Première')
        ->and($notes[0]->getId())->not->toBeNull();
});

test('TransactionRunner annule les écritures en cas d’exception', function (): void {
    $kernel = doctrineKernel();
    doctrineSchema($kernel);

    /** @var NoteRepositoryInterface $repository */
    $repository = $kernel->container()->get(NoteRepositoryInterface::class);
    $repository->create('Conservée');

    /** @var TransactionRunner $runner */
    $runner = $kernel->container()->get(TransactionRunner::class);

    try {
        $runner->run(function (EntityManagerInterface $manager): void {
            $manager->persist(new Note('Annulée'));
            throw new RuntimeException('échec volontaire');
        });
    } catch (RuntimeException) {
        // attendu
    }

    expect($repository->all())->toHaveCount(1);
});
