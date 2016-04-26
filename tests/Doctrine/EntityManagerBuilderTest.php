<?php
/**
 * Slim3 Doctrine integration (https://github.com/juliangut/slim-doctrine)
 *
 * @link https://github.com/juliangut/slim-doctrine for the canonical source repository
 *
 * @license https://raw.githubusercontent.com/juliangut/slim-doctrine/master/LICENSE
 */

namespace Jgut\Slim\Doctrine\Tests;

use Jgut\Slim\Doctrine\EntityManagerBuilder;
use Doctrine\Common\Proxy\AbstractProxyFactory;

/**
 * @covers Jgut\Slim\Doctrine\EntityManagerBuilder
 */
class EntityManagerBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testBadCacheDriver()
    {
        $options = [
            'cache_driver' => 'notValid',
        ];

        EntityManagerBuilder::build($options);
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

        EntityManagerBuilder::build($options);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testBadNamingStrategy()
    {
        $options = [
            'annotation_paths' => sys_get_temp_dir(),
            'naming_strategy' => 'notValid',
        ];

        EntityManagerBuilder::build($options);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testBadQuoteStrategy()
    {
        $options = [
            'annotation_paths' => sys_get_temp_dir(),
            'quote_strategy' => 'notValid',
        ];

        EntityManagerBuilder::build($options);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testNoCreation()
    {
        $options = [
            'annotation_paths' => sys_get_temp_dir(),
            'proxies_namespace' => 'myNamespace\Proxies',
            'auto_generate_proxies' => AbstractProxyFactory::AUTOGENERATE_ALWAYS,
            'sql_logger' => new \Doctrine\DBAL\Logging\EchoSQLLogger,
        ];

        EntityManagerBuilder::build($options);
    }

    public function testCreationFromAnnotationFile()
    {
        $options = [
            'connection' => [
                'driver' => 'pdo_sqlite',
                'memory' => true,
            ],
            'annotation_paths' => sys_get_temp_dir(),
        ];

        self::assertInstanceOf('\Doctrine\ORM\EntityManager', EntityManagerBuilder::build($options));
    }

    public function testCreationFromXMLFile()
    {
        $options = [
            'connection' => [
                'driver' => 'pdo_sqlite',
                'memory' => true,
            ],
            'xml_paths' => [dirname(__DIR__) . '/files/fakeAnnotationFile.php'],
        ];

        self::assertInstanceOf('\Doctrine\ORM\EntityManager', EntityManagerBuilder::build($options));
    }

    public function testCreationFromYAMLFile()
    {
        $options = [
            'connection' => [
                'driver' => 'pdo_sqlite',
                'memory' => true,
            ],
            'yaml_paths' => [dirname(__DIR__) . '/files/fakeAnnotationFile.php'],
        ];

        self::assertInstanceOf('\Doctrine\ORM\EntityManager', EntityManagerBuilder::build($options));
    }

    public function testCreationFromPHPFile()
    {
        $options = [
            'connection' => [
                'driver' => 'pdo_sqlite',
                'memory' => true,
            ],
            'php_paths' => [dirname(__DIR__) . '/files/fakeAnnotationFile.php'],
        ];

        self::assertInstanceOf('\Doctrine\ORM\EntityManager', EntityManagerBuilder::build($options));
    }

    public function testCustomTypes()
    {
        $options = [
            'connection' => [
                'driver' => 'pdo_sqlite',
                'memory' => true,
            ],
            'annotation_paths' => sys_get_temp_dir(),
            'custom_types' => [
                'custom' => '\Doctrine\DBAL\Types\DecimalType',
            ],
        ];

        self::assertInstanceOf('\Doctrine\ORM\EntityManager', EntityManagerBuilder::build($options));
    }
}
