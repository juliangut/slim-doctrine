<?php

/*
 * (c) 2016-2024 Julián Gutiérrez <juliangut@gmail.com>
 *
 * @license BSD-3-Clause
 * @link https://github.com/juliangut/slim-doctrine
 */

declare(strict_types=1);

namespace Jgut\Slim\Doctrine\Tests\Repository;

use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Hydrator\HydratorFactory;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Jgut\Slim\Doctrine\Repository\MongoDbRepository;
use Jgut\Slim\Doctrine\Repository\MongoDbRepositoryFactory;
use Jgut\Slim\Doctrine\Tests\Stubs\ObjectStub;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class MongoDbRepositoryFactoryTest extends TestCase
{
    public function testFindByOrFail(): void
    {
        $classMetadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $classMetadata->method('getName')
            ->willReturn(ObjectStub::class);

        $configuration = $this->getMockBuilder(Configuration::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configuration->method('getDefaultDocumentRepositoryClassName')
            ->willReturn(MongoDbRepository::class);

        $documentManager = $this->getMockBuilder(DocumentManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $documentManager->method('getClassMetadata')
            ->willReturn($classMetadata);
        $documentManager->method('getConfiguration')
            ->willReturn($configuration);

        $eventManager = $this->getMockBuilder(EventManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $unitOfWork = new UnitOfWork(
            $documentManager,
            $eventManager,
            new HydratorFactory(
                $documentManager,
                $eventManager,
                __DIR__,
                'NS',
                Configuration::AUTOGENERATE_NEVER,
            ),
        );

        $documentManager->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $factory = new MongoDbRepositoryFactory();
        $repository = $factory->getRepository($documentManager, ObjectStub::class);

        static::assertInstanceOf(MongoDbRepository::class, $repository);
    }
}
