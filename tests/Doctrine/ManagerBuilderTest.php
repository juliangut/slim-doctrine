<?php

/*
 * Slim3 Doctrine integration (https://github.com/juliangut/slim-doctrine).
 *
 * @license BSD-3-Clause
 * @link https://github.com/juliangut/slim-doctrine
 * @author Julián Gutiérrez <juliangut@gmail.com>
 */

namespace Jgut\Slim\Doctrine\Tests;

use Doctrine\ODM\CouchDB\DocumentManager as CouchDBDocumentManager;
use Doctrine\ODM\MongoDB\DocumentManager as MongoDBDocumentManager;
use Doctrine\ORM\EntityManager;
use Jgut\Doctrine\ManagerBuilder\AbstractManagerBuilder;
use Jgut\Slim\Doctrine\ManagerBuilder;

/**
 * Manager builder tests.
 */
class ManagerBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testSingleManagers()
    {
        $settings = [
            'entity_manager' => [
                'connection' => [
                    'driver' => 'pdo_sqlite',
                    'memory' => true,
                ],
                'metadata_mapping' => [
                    [
                        'type' => ManagerBuilder::METADATA_MAPPING_ANNOTATION,
                        'path' => __DIR__,
                    ],
                ],
            ],
            'mongodb_document_manager' => [
                'connection' => [
                    'server' => 'mongodb://localhost:27017',
                    'options' => ['connect' => false],
                ],
                'metadata_mapping' => [
                    [
                        'type' => ManagerBuilder::METADATA_MAPPING_ANNOTATION,
                        'path' => __DIR__,
                    ],
                ],
            ],
            'couchdb_document_manager' => [
                'connection' => [
                    'host' => 'localhost',
                    'dbname' => 'doctrine',
                ],
                'metadata_mapping' => [
                    [
                        'type' => ManagerBuilder::METADATA_MAPPING_ANNOTATION,
                        'path' => __DIR__,
                    ],
                ],
            ],
        ];

        $managerBuilder = (new ManagerBuilder())->loadSettings($settings);

        $managers = $managerBuilder->getManagers();

        self::assertCount(3, $managers);

        self::assertInstanceOf(EntityManager::class, $managerBuilder->getManager('entityManager'));
        self::assertEquals($managers['entityManager'], $managerBuilder->getManager('entityManager'));

        self::assertInstanceOf(MongoDBDocumentManager::class, $managerBuilder->getManager('mongoDocumentManager'));
        self::assertEquals($managers['mongoDocumentManager'], $managerBuilder->getManager('mongoDocumentManager'));

        self::assertInstanceOf(CouchDBDocumentManager::class, $managerBuilder->getManager('couchDocumentManager'));
        self::assertEquals($managers['couchDocumentManager'], $managerBuilder->getManager('couchDocumentManager'));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage "managerOne" manager builder is already registered
     */
    public function testDuplicatedNamedManager()
    {
        $settings = [
            'entity_manager' => [
                'managerOne' => [
                    'connection' => [
                        'driver' => 'pdo_sqlite',
                        'memory' => true,
                    ],
                    'metadata_mapping' => [
                        [
                            'type' => ManagerBuilder::METADATA_MAPPING_ANNOTATION,
                            'path' => __DIR__,
                        ],
                    ],
                ],
            ],
            'mongodb_document_manager' => [
                'managerOne' => [
                    'connection' => [
                        'server' => 'mongodb://localhost:27017',
                        'options' => ['connect' => false],
                    ],
                    'metadata_mapping' => [
                        [
                            'type' => ManagerBuilder::METADATA_MAPPING_ANNOTATION,
                            'path' => __DIR__,
                        ],
                    ],
                ],
            ],
        ];

        (new ManagerBuilder())->loadSettings($settings);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage "noOnesManager" is not a registered manager
     */
    public function testNoRegisteredManager()
    {
        self::assertCount(0, (new ManagerBuilder())->getManagers());

        $settings = [
            'entity_manager' => [
                'connection' => [
                    'driver' => 'pdo_sqlite',
                    'memory' => true,
                ],
                'metadata_mapping' => [
                    [
                        'type' => ManagerBuilder::METADATA_MAPPING_ANNOTATION,
                        'path' => __DIR__,
                    ],
                ],
            ],
        ];

        $managerBuilder = (new ManagerBuilder())->loadSettings($settings);

        $managerBuilder->getManager('noOnesManager');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Only named manager builders allowed
     */
    public function testUnnamedBuilder()
    {
        $builder = $this->getMockBuilder(AbstractManagerBuilder::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        /* @var AbstractManagerBuilder $builder */

        (new ManagerBuilder())->addBuilder($builder);
    }

    public function testCLIApplication()
    {
        $settings = [
            'entity_manager' => [
                'managerOne' => [
                    'connection' => [
                        'driver' => 'pdo_sqlite',
                        'memory' => true,
                    ],
                    'metadata_mapping' => [
                        [
                            'type' => ManagerBuilder::METADATA_MAPPING_ANNOTATION,
                            'path' => __DIR__,
                        ],
                    ],
                ],
            ],
            'mongodb_document_manager' => [
                'managerTwo' => [
                    'connection' => [
                        'server' => 'mongodb://localhost:27017',
                        'options' => ['connect' => false],
                    ],
                    'metadata_mapping' => [
                        [
                            'type' => ManagerBuilder::METADATA_MAPPING_ANNOTATION,
                            'path' => __DIR__,
                        ],
                    ],
                ],
            ],
        ];

        $managerBuilder = (new ManagerBuilder())->loadSettings($settings);

        $application = $managerBuilder->getCLIApplication();

        self::assertTrue($application->has('managerOne:dbal:run-sql'));
        self::assertTrue($application->has('managerTwo:odm:query'));
    }
}
