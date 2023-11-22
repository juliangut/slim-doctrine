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
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Iterator\CachingIterator;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/**
 * @template T of object
 *
 * @extends DocumentRepository<T>
 */
class MongoDbRepository extends DocumentRepository
{
    /** @use RepositoryBehaviour<T> */
    use RepositoryBehaviour;

    /**
     * @param array<string, mixed> $criteria
     */
    public function count(array $criteria): int
    {
        /** @var CachingIterator<T> $iterator */
        $iterator = $this->getDocumentPersister()
            ->loadAll($criteria);

        return $iterator->count();
    }

    protected function getManager(): DocumentManager
    {
        return $this->getDocumentManager();
    }

    /**
     * @return class-string<T>
     */
    public function getClassName(): string
    {
        return ClassUtils::getRealClass($this->getDocumentName());
    }
}
