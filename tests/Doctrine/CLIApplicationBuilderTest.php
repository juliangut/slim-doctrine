<?php
/**
 * Slim3 Doctrine integration (https://github.com/juliangut/slim-doctrine)
 *
 * @link https://github.com/juliangut/slim-doctrine for the canonical source repository
 *
 * @license https://raw.githubusercontent.com/juliangut/slim-doctrine/master/LICENSE
 */

namespace Jgut\Slim\Doctrine\Tests;

use Jgut\Slim\Doctrine\CLIApplicationBuilder;

/**
 * @covers Jgut\Slim\Doctrine\CLIApplicationBuilder
 */
class CLIApplicationBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testNoManagers()
    {
        CLIApplicationBuilder::build();
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testBadEntityManagers()
    {
        CLIApplicationBuilder::build('');
    }

    public function testEntityManager()
    {
        $entityOptions = [
            'connection' => [
                'driver' => 'pdo_sqlite',
                'memory' => true,
            ],
            'annotation_paths' => sys_get_temp_dir(),
        ];

        $application = CLIApplicationBuilder::build($entityOptions);

        self::assertInstanceOf('Symfony\Component\Console\Application', $application);
        self::assertTrue($application->has('orm:schema-tool:create'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testBadDocumentManagers()
    {
        CLIApplicationBuilder::build(null, '');
    }

    public function testDocumentManagers()
    {
        $documentOptions = [
            'connection' => [
                'server' => 'mongodb://localhost:27017',
            ],
            'annotation_paths' => sys_get_temp_dir(),
        ];

        $application = CLIApplicationBuilder::build(null, $documentOptions);

        self::assertInstanceOf('Symfony\Component\Console\Application', $application);
        self::assertTrue($application->has('odm:generate:documents'));
        self::assertTrue($application->has('odm:generate:hydrators'));
        self::assertTrue($application->has('odm:generate:proxies'));
        self::assertTrue($application->has('odm:generate:repositories'));
        self::assertTrue($application->has('odm:clear-cache:metadata'));
        self::assertTrue($application->has('odm:schema:create'));
        self::assertTrue($application->has('odm:schema:drop'));
        self::assertTrue($application->has('odm:schema:update'));
    }
}
