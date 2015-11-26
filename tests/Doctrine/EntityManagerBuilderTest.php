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
     * @cover \Jgut\Slim\Doctrine\EntityManagerBuilder::build
     *
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
     * @cover \Jgut\Slim\Doctrine\EntityManagerBuilder::build
     * @cover \Jgut\Slim\Doctrine\EntityManagerBuilder::setupAnnotationMetadata
     *
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

        EntityManagerBuilder::build($options);
    }

    /**
     * @cover \Jgut\Slim\Doctrine\EntityManagerBuilder::build
     * @cover \Jgut\Slim\Doctrine\EntityManagerBuilder::createConfiguration
     * @cover \Jgut\Slim\Doctrine\EntityManagerBuilder::normalizePaths
     *
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
     * @cover \Jgut\Slim\Doctrine\EntityManagerBuilder::build
     * @cover \Jgut\Slim\Doctrine\EntityManagerBuilder::setupProxy
     * @cover \Jgut\Slim\Doctrine\EntityManagerBuilder::setupSQLLogger
     *
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

    /**
     * @cover \Jgut\Slim\Doctrine\EntityManagerBuilder::build
     * @cover \Jgut\Slim\Doctrine\EntityManagerBuilder::createConfiguration
     */
    public function testAnnotationsCreation()
    {
        $options = [
            'connection' => [
                'driver' => 'pdo_sqlite',
                'memory' => true,
            ],
            'annotation_paths' => sys_get_temp_dir(),
        ];

        $this->assertInstanceOf('\Doctrine\ORM\EntityManager', EntityManagerBuilder::build($options));
    }

    /**
     * @cover \Jgut\Slim\Doctrine\EntityManagerBuilder::build
     * @cover \Jgut\Slim\Doctrine\EntityManagerBuilder::createConfiguration
     */
    public function testXMLCreation()
    {
        $options = [
            'connection' => [
                'driver' => 'pdo_sqlite',
                'memory' => true,
            ],
            'xml_paths' => [dirname(__DIR__) . '/files/fakeAnnotationFile.php'],
        ];

        $this->assertInstanceOf('\Doctrine\ORM\EntityManager', EntityManagerBuilder::build($options));
    }

    /**
     * @cover \Jgut\Slim\Doctrine\EntityManagerBuilder::build
     * @cover \Jgut\Slim\Doctrine\EntityManagerBuilder::createConfiguration
     */
    public function testYAMLCreation()
    {
        $options = [
            'connection' => [
                'driver' => 'pdo_sqlite',
                'memory' => true,
            ],
            'yaml_paths' => [dirname(__DIR__) . '/files/fakeAnnotationFile.php'],
        ];

        $this->assertInstanceOf('\Doctrine\ORM\EntityManager', EntityManagerBuilder::build($options));
    }
}
