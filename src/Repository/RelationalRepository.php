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

/**
 * @template T of object
 *
 * @extends  EntityRepository<T>
 */
class RelationalRepository extends EntityRepository
{
    /** @use RepositoryBehaviour<T> */
    use RepositoryBehaviour;

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
