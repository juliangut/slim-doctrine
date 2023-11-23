<?php

/*
 * (c) 2016-2023 Julián Gutiérrez <juliangut@gmail.com>
 *
 * @license BSD-3-Clause
 * @link https://github.com/juliangut/slim-doctrine
 */

declare(strict_types=1);

namespace Jgut\Slim\Doctrine\Repository;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\UnitOfWork;

/**
 * @template T of object
 *
 * @extends  EntityRepository<T>
 */
class RelationalRepository extends EntityRepository
{
    /** @use RepositoryBehaviour<T> */
    use RepositoryBehaviour;

    public function isObjectManaged(object $object): bool
    {
        return $this->getObjectState($object) === UnitOfWork::STATE_MANAGED;
    }

    public function isObjectNew(object $object): bool
    {
        return $this->getObjectState($object) === UnitOfWork::STATE_NEW;
    }

    public function isObjectDetached(object $object): bool
    {
        return $this->getObjectState($object) === UnitOfWork::STATE_DETACHED;
    }

    public function isObjectRemoved(object $object): bool
    {
        return $this->getObjectState($object) === UnitOfWork::STATE_REMOVED;
    }

    /**
     * @return UnitOfWork::STATE_*
     */
    protected function getObjectState(object $object): int
    {
        return $this->getManager()
            ->getUnitOfWork()
            ->getEntityState($object);
    }

    protected function getManager(): EntityManager
    {
        return $this->getEntityManager();
    }

    /**
     * @return class-string<T>
     */
    public function getClassName(): string
    {
        return ClassUtils::getRealClass($this->getEntityName());
    }
}
