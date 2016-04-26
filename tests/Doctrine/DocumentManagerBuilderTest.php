<?php
/**
 * Slim3 Doctrine integration (https://github.com/juliangut/slim-doctrine)
 *
 * @link https://github.com/juliangut/slim-doctrine for the canonical source repository
 *
 * @license https://raw.githubusercontent.com/juliangut/slim-doctrine/master/LICENSE
 */

namespace Jgut\Slim\Doctrine\Tests;

use Doctrine\Common\EventManager;
use Doctrine\MongoDB\Connection;
use Jgut\Slim\Doctrine\DocumentManagerBuilder;
use Doctrine\Common\Proxy\AbstractProxyFactory;

/**
 * @covers Jgut\Slim\Doctrine\DocumentManagerBuilder
 */
class DocumentManagerBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testBadCacheDriver()
    {
        $options = [
            'cache_driver' => 'notValid',
        ];

        DocumentManagerBuilder::build($options);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testNoMetadata()
    {
        $options = [
            'annotation_files' => [dirname(__DIR__) . '/files/fakeAnnotationFile.php'],
            'annotation_namespaces' => ['\Jgut\Slim\Doctrine'],
            'annotation_autoloaders' => [function () {
            }],
        ];

        DocumentManagerBuilder::build($options);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testNoConnection()
    {
        $options = [
            'annotation_paths' => sys_get_temp_dir(),
            'default_database' => 'test',
            'proxies_namespace' => 'myNamespace\Proxies',
            'auto_generate_proxies' => AbstractProxyFactory::AUTOGENERATE_ALWAYS,
            'hydrators_namespace' => 'myNamespace\Hydrators',
            'logger_callable' => function () {
            },
        ];

        DocumentManagerBuilder::build($options);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testBadEventManager()
    {
        $options = [
            'connection' => new Connection(),
            'annotation_paths' => sys_get_temp_dir(),
            'default_database' => 'test',
            'proxies_namespace' => 'myNamespace\Proxies',
            'auto_generate_proxies' => AbstractProxyFactory::AUTOGENERATE_ALWAYS,
            'hydrators_namespace' => 'myNamespace\Hydrators',
            'logger_callable' => function () {
            },
            'event_manager' => new EventManager()
        ];

        DocumentManagerBuilder::build($options);
    }

    public function testCreationFromAnnotationFile()
    {
        $options = [
            'connection' => new Connection('mongodb://localhost:27017'),
            'annotation_paths' => sys_get_temp_dir(),
        ];

        self::assertInstanceOf('\Doctrine\ODM\MongoDB\DocumentManager', DocumentManagerBuilder::build($options));
    }

    public function testCreationFromXMLFile()
    {
        $options = [
            'connection' => [
                'server' => 'mongodb://localhost:27017',
            ],
            'xml_paths' => [dirname(__DIR__) . '/files/fakeAnnotationFile.php'],
        ];

        self::assertInstanceOf('\Doctrine\ODM\MongoDB\DocumentManager', DocumentManagerBuilder::build($options));
    }

    public function testCreationFromYAMLFile()
    {
        $options = [
            'connection' => [
                'server' => 'mongodb://localhost:27017',
            ],
            'yaml_paths' => [dirname(__DIR__) . '/files/fakeAnnotationFile.php'],
        ];

        self::assertInstanceOf('\Doctrine\ODM\MongoDB\DocumentManager', DocumentManagerBuilder::build($options));
    }

    public function testCreationFromPHPFile()
    {
        $options = [
            'connection' => [
                'server' => 'mongodb://localhost:27017',
            ],
            'php_paths' => [dirname(__DIR__) . '/files/fakeAnnotationFile.php'],
        ];

        self::assertInstanceOf('\Doctrine\ODM\MongoDB\DocumentManager', DocumentManagerBuilder::build($options));
    }
}
