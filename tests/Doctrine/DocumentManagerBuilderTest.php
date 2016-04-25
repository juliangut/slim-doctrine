<?php
/**
 * Slim3 Doctrine integration (https://github.com/juliangut/slim-doctrine)
 *
 * @link https://github.com/juliangut/slim-doctrine for the canonical source repository
 *
 * @license https://raw.githubusercontent.com/juliangut/slim-doctrine/master/LICENSE
 */

namespace Jgut\Slim\Doctrine\Tests;

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
     * @expectedException \InvalidArgumentException
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
    public function testNoCreation()
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

    public function testAnnotationsCreation()
    {
        $options = [
            'connection' => [
                'server' => 'mongodb://localhost:27017',
            ],
            'annotation_paths' => sys_get_temp_dir(),
        ];

        self::assertInstanceOf('\Doctrine\ODM\MongoDB\DocumentManager', DocumentManagerBuilder::build($options));
    }

    public function testXMLCreation()
    {
        $options = [
            'connection' => [
                'server' => 'mongodb://localhost:27017',
            ],
            'xml_paths' => [dirname(__DIR__) . '/files/fakeAnnotationFile.php'],
        ];

        self::assertInstanceOf('\Doctrine\ODM\MongoDB\DocumentManager', DocumentManagerBuilder::build($options));
    }

    public function testYAMLCreation()
    {
        $options = [
            'connection' => [
                'server' => 'mongodb://localhost:27017',
            ],
            'yaml_paths' => [dirname(__DIR__) . '/files/fakeAnnotationFile.php'],
        ];

        self::assertInstanceOf('\Doctrine\ODM\MongoDB\DocumentManager', DocumentManagerBuilder::build($options));
    }
}
