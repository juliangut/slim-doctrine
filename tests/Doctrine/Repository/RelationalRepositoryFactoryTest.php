<?php

/*
 * (c) 2016-2023 Julián Gutiérrez <juliangut@gmail.com>
 *
 * @license BSD-3-Clause
 * @link https://github.com/juliangut/slim-doctrine
 */

declare(strict_types=1);

namespace Jgut\Slim\Doctrine\Tests\Repository;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Jgut\Slim\Doctrine\Repository\RelationalRepository;
use Jgut\Slim\Doctrine\Repository\RelationalRepositoryFactory;
use Jgut\Slim\Doctrine\Tests\Stubs\ObjectStub;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class RelationalRepositoryFactoryTest extends TestCase
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
        $configuration->method('getDefaultRepositoryClassName')
            ->willReturn(RelationalRepository::class);

        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->method('getClassMetadata')
            ->willReturn($classMetadata);
        $entityManager->method('getConfiguration')
            ->willReturn($configuration);

        $factory = new RelationalRepositoryFactory();
        $repository = $factory->getRepository($entityManager, ObjectStub::class);

        static::assertInstanceOf(RelationalRepository::class, $repository);
    }
}
