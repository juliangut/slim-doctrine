<?php

/*
 * (c) 2016-2024 Julián Gutiérrez <juliangut@gmail.com>
 *
 * @license BSD-3-Clause
 * @link https://github.com/juliangut/slim-doctrine
 */

declare(strict_types=1);

namespace Jgut\Slim\Doctrine\Tests\Stubs;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Jgut\Slim\Doctrine\Repository\RepositoryBehaviour;

/**
 * @internal
 */
class RelationalRepositoryStub
{
    use RepositoryBehaviour;

    public function __construct(
        private ?EntityManager $entityManager,
        /**
         * @var list<ObjectStub>
         */
        private array $entities = [],
        private ?ClassMetadata $classMetadata = null,
    ) {}

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function find(mixed $key, mixed $lockMode = null, mixed $lockVersion = null): ?ObjectStub
    {
        return $this->findOneBy(\is_array($key) ? $key : ['key' => $key]);
    }

    /**
     * @param array{key?: string}       $criteria
     * @param array<string, mixed>|null $orderBy
     *
     * @return list<ObjectStub>
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        if ($criteria === []) {
            return array_values($this->entities);
        }

        return array_filter(
            $this->entities,
            static fn(ObjectStub $entity): bool
            => preg_match(sprintf('/%s/', $criteria['key']), $entity->key) === 1,
        );
    }

    /**
     * @param array{key?: string}       $criteria
     * @param array<string, mixed>|null $orderBy
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?ObjectStub
    {
        if ($criteria === []) {
            return $this->entities[0] ?? null;
        }

        $entities = array_values(array_filter(
            $this->entities,
            static fn(ObjectStub $entity): bool
            => preg_match(sprintf('/%s/', $criteria['key']), $entity->key) === 1,
        ));

        return $entities[0] ?? null;
    }

    public function count(array $criteria): int
    {
        return \count($criteria === [] ? $this->entities : $this->findBy($criteria));
    }

    protected function getManager(): EntityManager
    {
        return $this->entityManager;
    }

    public function getClassName(): string
    {
        return ObjectStub::class;
    }

    protected function getClassMetadata(): ClassMetadata
    {
        return $this->classMetadata;
    }
}
