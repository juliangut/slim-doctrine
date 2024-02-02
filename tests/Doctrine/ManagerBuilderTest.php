<?php

/*
 * (c) 2016-2024 Julián Gutiérrez <juliangut@gmail.com>
 *
 * @license BSD-3-Clause
 * @link https://github.com/juliangut/slim-doctrine
 */

declare(strict_types=1);

namespace Jgut\Slim\Doctrine\Tests;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use InvalidArgumentException;
use Jgut\Doctrine\ManagerBuilder\AbstractManagerBuilder;
use Jgut\Slim\Doctrine\ManagerBuilder;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @internal
 */
class ManagerBuilderTest extends TestCase
{
    public function testInvalidConfigurationOption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown configuration "option".');

        new ManagerBuilder(['option' => 'value']);
    }

    public function testDuplicatedNamedManager(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('"managerOne" manager builder is already registered.');

        $settings = [
            'entity_manager' => [
                'managerOne' => [
                    'connection' => [
                        'driver' => 'pdo_sqlite',
                        'memory' => true,
                    ],
                    'metadataMapping' => [
                        [
                            'type' => ManagerBuilder::METADATA_MAPPING_ATTRIBUTE,
                            'path' => __DIR__,
                        ],
                    ],
                ],
            ],
            'document_manager' => [
                'managerOne' => [
                    'client' => [
                        'uri' => 'mongodb://localhost:27017',
                        'driverOptions' => ['connect' => false],
                    ],
                    'metadataMapping' => [
                        [
                            'type' => ManagerBuilder::METADATA_MAPPING_ATTRIBUTE,
                            'path' => __DIR__,
                        ],
                    ],
                ],
            ],
        ];

        (new ManagerBuilder())->registerManagers($settings);
    }

    public function testUnnamedBuilder(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Only named manager builders allowed.');

        $builder = $this->getMockBuilder(AbstractManagerBuilder::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        (new ManagerBuilder())->addBuilder($builder);
    }

    public function testDefaultManagers(): void
    {
        $settings = [
            'entity_manager' => [
                'connection' => [
                    'driver' => 'pdo_sqlite',
                    'memory' => true,
                ],
                'metadataMapping' => [
                    [
                        'type' => ManagerBuilder::METADATA_MAPPING_ATTRIBUTE,
                        'path' => __DIR__,
                    ],
                ],
            ],
            'document_manager' => [
                'client' => [
                    'uri' => 'mongodb://localhost:27017',
                    'driverOptions' => ['connect' => false],
                ],
                'metadataMapping' => [
                    [
                        'type' => ManagerBuilder::METADATA_MAPPING_ATTRIBUTE,
                        'path' => __DIR__,
                    ],
                ],
            ],
        ];

        $managerBuilder = new ManagerBuilder();
        $managerBuilder->registerManagers($settings);

        $managers = $managerBuilder->getManagers();

        static::assertCount(2, $managers);

        static::assertInstanceOf(EntityManager::class, $managerBuilder->getManager('entityManager'));
        static::assertEquals($managers['entityManager'], $managerBuilder->getManager('entityManager'));

        static::assertInstanceOf(DocumentManager::class, $managerBuilder->getManager('documentManager'));
        static::assertEquals($managers['documentManager'], $managerBuilder->getManager('documentManager'));
    }

    public function testNotRegisteredNamedManager(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('"customManager" is not a registered manager');

        static::assertCount(0, (new ManagerBuilder())->getManagers());

        $settings = [
            [
                'connection' => [
                    'driver' => 'pdo_sqlite',
                    'memory' => true,
                ],
                'metadataMapping' => [
                    [
                        'type' => ManagerBuilder::METADATA_MAPPING_ATTRIBUTE,
                        'path' => __DIR__,
                    ],
                ],
            ],
        ];

        $managerBuilder = new ManagerBuilder();
        $managerBuilder->registerRelationalManagers($settings);

        $managers = $managerBuilder->getManagers();

        static::assertCount(1, $managers);

        $managerBuilder->getManager('customManager');
    }

    public function testRegisteredNamedManager(): void
    {
        static::assertCount(0, (new ManagerBuilder())->getManagers());

        $settings = [
            'customManager' => [
                'client' => [
                    'uri' => 'mongodb://localhost',
                    'driverOptions' => ['connect' => false],
                ],
                'metadataMapping' => [
                    [
                        'type' => ManagerBuilder::METADATA_MAPPING_ATTRIBUTE,
                        'path' => __DIR__,
                    ],
                ],
            ],
        ];

        $managerBuilder = new ManagerBuilder();
        $managerBuilder->registerMongoDbDocumentManagers($settings);

        $managers = $managerBuilder->getManagers();

        static::assertCount(1, $managers);

        static::assertInstanceOf(DocumentManager::class, $managerBuilder->getManager('customManager'));
        static::assertEquals($managers['customManager'], $managerBuilder->getManager('customManager'));
    }

    public function testCommandLineApplication(): void
    {
        $settings = [
            'entity_manager' => [
                'first' => [
                    'connection' => [
                        'driver' => 'pdo_sqlite',
                        'memory' => true,
                    ],
                    'metadataMapping' => [
                        [
                            'type' => ManagerBuilder::METADATA_MAPPING_ATTRIBUTE,
                            'path' => __DIR__,
                        ],
                    ],
                ],
                'second' => [
                    'connection' => [
                        'driver' => 'pdo_sqlite',
                        'memory' => true,
                    ],
                    'metadataMapping' => [
                        [
                            'type' => ManagerBuilder::METADATA_MAPPING_ATTRIBUTE,
                            'path' => __DIR__,
                        ],
                    ],
                    'migrationsConfiguration' => [
                        'migrations_paths' => [
                            'App\Migrations' => __DIR__ . '/files/migrations',
                        ],
                    ],
                ],
            ],
            'document_manager' => [
                'third' => [
                    'client' => [
                        'uri' => 'mongodb://localhost:27017',
                        'driverOptions' => ['connect' => false],
                    ],
                    'metadataMapping' => [
                        [
                            'type' => ManagerBuilder::METADATA_MAPPING_ATTRIBUTE,
                            'path' => __DIR__,
                        ],
                    ],
                ],
            ],
        ];

        $managerBuilder = new ManagerBuilder();
        $managerBuilder->registerManagers($settings);

        $application = $managerBuilder->getCliApplication('custom');

        static::assertTrue($application->has('custom:dbal-first:run-sql'));
        static::assertTrue($application->has('custom:orm-first:schema-tool:create'));

        static::assertTrue($application->has('custom:dbal-second:run-sql'));
        static::assertTrue($application->has('custom:orm-second:schema-tool:create'));
        static::assertTrue($application->has('custom:migrations-second:status'));

        static::assertTrue($application->has('custom:odm-third:query'));
    }
}
