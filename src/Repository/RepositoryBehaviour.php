<?php

/*
 * (c) 2016-2023 Julián Gutiérrez <juliangut@gmail.com>
 *
 * @license BSD-3-Clause
 * @link https://github.com/juliangut/slim-doctrine
 */

declare(strict_types=1);

namespace Jgut\Slim\Doctrine\Repository;

use BadMethodCallException;
use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Doctrine\Persistence\ObjectManager;
use InvalidArgumentException;
use Jgut\Slim\Doctrine\Exception\NotFoundException;
use Jgut\Slim\Doctrine\Exception\UnsupportedObjectException;

/**
 * @template T of object
 */
trait RepositoryBehaviour
{
    protected ?Inflector $inflector = null;

    /**
     * @param string      $method
     * @param list<mixed> $arguments
     *
     * @throws BadMethodCallException
     */
    public function __call($method, $arguments): mixed
    {
        if (\count($arguments) === 0) {
            throw new BadMethodCallException(
                sprintf('You need to pass a parameter to %s::%s.', $this::class, $method),
            );
        }

        $supportingMethod = $this->getSupportingMethod($method);

        if (str_ends_with($method, 'OrFail') && \in_array($supportingMethod, ['findBy', 'findOneBy'], true)) {
            $fieldName = mb_substr($method, mb_strlen($supportingMethod), -6);
            $supportingMethod .= 'OrFail';
        } elseif (str_ends_with($method, 'OrGetNew') && $supportingMethod === 'findOneBy') {
            $fieldName = mb_substr($method, mb_strlen($supportingMethod), -8);
            $supportingMethod = 'findOneByOrGetNew';
        } elseif ($supportingMethod === 'countBy') {
            $fieldName = mb_substr($method, 7);
            $supportingMethod = 'count';
        } else {
            $fieldName = mb_substr($method, mb_strlen($supportingMethod));
        }

        return $this->callSupportingMethod($supportingMethod, $fieldName, $arguments);
    }

    protected function getSupportingMethod(string $method): string
    {
        static $supportingMethods = [
            'findBy',
            'findOneBy',
            'countBy',
            'removeBy',
            'removeOneBy',
        ];

        foreach ($supportingMethods as $supportingMethod) {
            if (str_starts_with($method, $supportingMethod)) {
                return $supportingMethod;
            }
        }

        throw new BadMethodCallException(sprintf(
            'Undefined method "%s". Method call must start with one of "%s".',
            $method,
            implode('", "', $supportingMethods),
        ));
    }

    /**
     * @param list<mixed> $arguments
     *
     * @throws BadMethodCallException
     */
    protected function callSupportingMethod(string $supportingMethod, string $fieldName, array $arguments): mixed
    {
        if ($this->inflector === null) {
            $this->inflector = InflectorFactory::create()->build();
        }

        /** @var callable $callable */
        $callable = [$this, $supportingMethod];

        if ($supportingMethod === 'count' && $fieldName === '') {
            return $callable(...$arguments);
        }

        $field = lcfirst($this->inflector->classify($fieldName));

        $classMetadata = $this->getClassMetadata();
        if (!$classMetadata->hasField($field) && !$classMetadata->hasAssociation($field)) {
            throw new BadMethodCallException(
                sprintf('Invalid call to %s::%s. Field "%s" does not exist.', $this::class, $supportingMethod, $field),
            );
        }

        return $callable([$field => $arguments[0]], ...\array_slice($arguments, 1));
    }

    public function countAll(): int
    {
        return $this->count([]);
    }

    /**
     * @param array<string, mixed>             $criteria
     * @param array<string, 'asc'|'desc'>|null $orderBy
     *
     * @throws NotFoundException
     *
     * @return non-empty-list<T>
     */
    public function findByOrFail(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): array {
        $objects = $this->findBy($criteria, $orderBy, $limit, $offset);
        if (\count($objects) === 0) {
            throw new NotFoundException();
        }

        return array_values($objects);
    }

    /**
     * @param array<string, mixed>             $criteria
     * @param array<string, 'asc'|'desc'>|null $orderBy
     *
     * @throws NotFoundException
     *
     * @return T
     */
    public function findOneByOrFail(array $criteria, ?array $orderBy = null): object
    {
        $object = $this->findOneBy($criteria, $orderBy);
        if ($object === null) {
            throw new NotFoundException();
        }

        return $object;
    }

    /**
     * @param array<string, mixed>             $criteria
     * @param array<string, 'asc'|'desc'>|null $orderBy
     *
     * @return T
     */
    public function findOneByOrGetNew(array $criteria, ?array $orderBy = null): object
    {
        $object = $this->findOneBy($criteria, $orderBy);
        if ($object === null) {
            $object = $this->getNewObject();
        }

        return $object;
    }

    /**
     * @return T
     */
    public function getNewObject(): object
    {
        $className = $this->getClassName();

        return new $className();
    }

    public function isObjectAttached(object $object): bool
    {
        $this->assertObjectsClass($object);

        return $this->getManager()
            ->contains($object);
    }

    /**
     * @param object|iterable<object> $objects
     */
    public function persist(object|iterable $objects, bool $flush = false): void
    {
        $this->assertObjectsClass($objects);

        $this->processManagerAction('persist', $objects, $flush);
    }

    /**
     * @param object|iterable<object>|array<string, mixed>|string|int $objects
     */
    public function remove(object|iterable|string|int $objects, bool $flush = false): void
    {
        if (\is_string($objects) || \is_int($objects) || (\is_array($objects) && !array_is_list($objects))) {
            $objects = $this->find($objects);
            if ($objects === null) {
                return;
            }
        } else {
            $this->assertObjectsClass($objects);
        }

        $this->processManagerAction('remove', $objects, $flush);
    }

    public function removeAll(bool $flush = false): void
    {
        $objects = $this->findAll();
        if (\count($objects) === 0) {
            return;
        }

        $this->processManagerAction('remove', $objects, $flush);
    }

    /**
     * @param array<string, mixed> $criteria
     */
    public function removeBy(array $criteria, ?int $offset = null, bool $flush = false): void
    {
        $objects = $this->findBy($criteria, null, $offset);
        if (\count($objects) === 0) {
            return;
        }

        $this->processManagerAction('remove', $objects, $flush);
    }

    /**
     * @param array<string, mixed>             $criteria
     * @param array<string, 'asc'|'desc'>|null $orderBy
     */
    public function removeOneBy(array $criteria, ?array $orderBy = null, bool $flush = false): void
    {
        $object = $this->findOneBy($criteria, $orderBy);
        if ($object === null) {
            return;
        }

        $this->processManagerAction('remove', $object, $flush);
    }

    /**
     * @param object|iterable<object> $objects
     */
    public function refresh(object|iterable $objects): void
    {
        $this->assertObjectsClass($objects);

        $this->processManagerAction('refresh', $objects, false);
    }

    public function flush(): void
    {
        $this->getManager()
            ->flush();
    }

    /**
     * @param object|iterable<object> $objects
     */
    public function merge(object|iterable $objects): void
    {
        $this->assertObjectsClass($objects);

        $this->processManagerAction('merge', $objects, false);
    }

    /**
     * @param object|iterable<object> $objects
     */
    public function detach(object|iterable $objects): void
    {
        $this->assertObjectsClass($objects);

        $this->processManagerAction('detach', $objects, false);
    }

    /**
     * @param object|iterable<object> $objects
     *
     * @throws UnsupportedObjectException
     *
     * @phpstan-assert T|iterable<T> $objects
     */
    private function assertObjectsClass(object|iterable $objects): void
    {
        if (!is_iterable($objects)) {
            $objects = [$objects];
        }

        $managedClass = $this->getClassName();

        foreach ($objects as $object) {
            if (!$object instanceof $managedClass) {
                throw new UnsupportedObjectException(
                    sprintf(
                        'Managed objects must be instances of "%s". "%s" given.',
                        $managedClass,
                        $object::class,
                    ),
                );
            }
        }
    }

    /**
     * @param T|iterable<T> $objects
     *
     * @throws InvalidArgumentException
     */
    private function processManagerAction(string $action, object|iterable $objects, bool $flush): void
    {
        /** @var callable $callable */
        $callable = [$this->getManager(), $action];

        if (!is_iterable($objects)) {
            $objects = [$objects];
        }

        foreach ($objects as $object) {
            $callable($object);
        }

        if ($flush) {
            $this->flush();
        }
    }

    abstract protected function getManager(): ObjectManager;

    /**
     * @return class-string<T>
     */
    abstract public function getClassName(): string;
}
