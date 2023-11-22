<?php

/*
 * (c) 2016-2023 Julián Gutiérrez <juliangut@gmail.com>
 *
 * @license BSD-3-Clause
 * @link https://github.com/juliangut/slim-doctrine
 */

declare(strict_types=1);

namespace Jgut\Slim\Doctrine\Tests\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Jgut\Slim\Doctrine\Exception\NotFoundException;
use Jgut\Slim\Doctrine\Exception\UnsupportedObjectException;
use Jgut\Slim\Doctrine\Tests\Stubs\ObjectStub;
use Jgut\Slim\Doctrine\Tests\Stubs\RelationalRepositoryStub;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 *
 * @internal
 */
class RepositoryBehaviourTest extends TestCase
{
    public function testFindByOrFail(): void
    {
        $manager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $classMetadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $classMetadata->method('hasField')
            ->willReturn(true);
        $classMetadata->method('hasAssociation')
            ->willReturn(true);

        $entity = new ObjectStub('second');

        $repository = new RelationalRepositoryStub($manager, [new ObjectStub('first'), $entity], $classMetadata);

        $recoveredEntities = $repository->findByOrFail(['key' => 'second']);

        static::assertIsArray($recoveredEntities);
        static::assertCount(1, $recoveredEntities);
        static::assertSame($entity, $recoveredEntities[0]);

        $recoveredEntities = $repository->findByKeyOrFail('second');

        static::assertIsArray($recoveredEntities);
        static::assertCount(1, $recoveredEntities);
        static::assertSame($entity, $recoveredEntities[0]);
    }

    public function testFailingFindByOrFail(): void
    {
        $this->expectException(NotFoundException::class);

        $manager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        (new RelationalRepositoryStub($manager, [new ObjectStub('first')]))
            ->findByOrFail(['key' => 'second']);
    }

    public function testFailingFindByKeyOrFail(): void
    {
        $this->expectException(NotFoundException::class);
        $classMetadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $classMetadata->method('hasField')
            ->willReturn(true);
        $classMetadata->method('hasAssociation')
            ->willReturn(true);

        $manager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        (new RelationalRepositoryStub($manager, [new ObjectStub('first')], $classMetadata))
            ->findByKeyOrFail('second');
    }

    public function testFindOneByOrFail(): void
    {
        $manager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $classMetadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $classMetadata->method('hasField')
            ->willReturn(true);
        $classMetadata->method('hasAssociation')
            ->willReturn(true);

        $entity = new ObjectStub('second');

        $repository = new RelationalRepositoryStub($manager, [new ObjectStub('first'), $entity], $classMetadata);

        $recoveredEntity = $repository->findOneByOrFail(['key' => 'second']);

        static::assertSame($entity, $recoveredEntity);

        $recoveredEntity = $repository->findOneByKeyOrFail('second');

        static::assertSame($entity, $recoveredEntity);
    }

    public function testFindOneByOrGetNew(): void
    {
        $manager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $classMetadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $classMetadata->method('hasField')
            ->willReturn(true);
        $classMetadata->method('hasAssociation')
            ->willReturn(true);

        $entity = new ObjectStub('first');

        $repository = new RelationalRepositoryStub($manager, [$entity], $classMetadata);

        $recoveredEntity = $repository->findOneByOrGetNew(['key' => 'first']);

        static::assertSame($entity, $recoveredEntity);

        $recoveredEntity = $repository->findOneByOrGetNew(['key' => 'second']);

        static::assertSame('none', $recoveredEntity->key);
    }

    public function testFailingFindOneByOrFail(): void
    {
        $this->expectException(NotFoundException::class);

        $manager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        (new RelationalRepositoryStub($manager, [new ObjectStub('first')]))
            ->findOneByOrFail(['key' => 'second']);
    }

    public function testFailingFindOneByKeyOrFail(): void
    {
        $this->expectException(NotFoundException::class);

        $classMetadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $classMetadata->method('hasField')
            ->willReturn(true);
        $classMetadata->method('hasAssociation')
            ->willReturn(true);

        $manager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        (new RelationalRepositoryStub($manager, [new ObjectStub('first')], $classMetadata))
            ->findOneByKeyOrFail('second');
    }

    public function testCountBy(): void
    {
        $manager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $classMetadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $classMetadata->method('hasField')
            ->willReturn(true);
        $classMetadata->method('hasAssociation')
            ->willReturn(true);

        $repository = new RelationalRepositoryStub(
            $manager,
            [new ObjectStub('first'), new ObjectStub('second')],
            $classMetadata,
        );

        static::assertSame(1, $repository->countBy(['key' => 'second']));

        static::assertSame(1, $repository->countByKey('second'));
    }

    public function testInvalidObject(): void
    {
        $this->expectException(UnsupportedObjectException::class);
        $this->expectExceptionMessageMatches(
            '/^Managed objects must be instances of ".+\\\ObjectStub"\. "stdClass" given\.$/',
        );

        $manager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository = new RelationalRepositoryStub($manager, []);

        $repository->persist([new stdClass()]);
    }

    public function testValidObject(): void
    {
        $entity = new ObjectStub('first');

        $manager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $manager->expects(static::once())
            ->method('persist')
            ->with($entity);

        $repository = new RelationalRepositoryStub($manager, []);

        $repository->persist($entity);
    }

    public function testRemoveFromFind(): void
    {
        $entity = new ObjectStub('first');

        $manager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $manager->expects(static::once())
            ->method('remove')
            ->with($entity);

        $repository = new RelationalRepositoryStub($manager, [$entity]);

        $repository->remove($entity->key);
    }

    public function testRemoveFromObject(): void
    {
        $entity = new ObjectStub('first');

        $manager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $manager->expects(static::once())
            ->method('remove')
            ->with($entity);

        $repository = new RelationalRepositoryStub($manager, []);

        $repository->remove($entity);
    }
}
