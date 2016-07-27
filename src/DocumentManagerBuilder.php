<?php
/**
 * Slim3 Doctrine integration (https://github.com/juliangut/slim-doctrine)
 *
 * @link https://github.com/juliangut/slim-doctrine for the canonical source repository
 *
 * @license https://raw.githubusercontent.com/juliangut/slim-doctrine/master/LICENSE
 */

namespace Jgut\Slim\Doctrine;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\Mapping\Driver\StaticPHPDriver;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\MongoDB\Mapping\Driver\XmlDriver;
use Doctrine\ODM\MongoDB\Mapping\Driver\YamlDriver;

/**
 * Doctrine Document Manager service builder.
 */
class DocumentManagerBuilder
{
    use ObjectManagerTrait;

    /**
     * Default configuration options.
     *
     * @var array
     */
    protected static $defaultOptions = [
        'connection' => null,
        'cache_driver' => null,
        'cache_namespace' => null,
        'annotation_files' => [],
        'annotation_namespaces' => [],
        'annotation_autoloaders' => [],
        'annotation_paths' => null,
        'xml_paths' => null,
        'yaml_paths' => null,
        'php_paths' => null,
        'default_database' => null,
        'proxy_path' => null,
        'proxies_namespace' => 'DoctrineODMProxy',
        'auto_generate_proxies' => AbstractProxyFactory::AUTOGENERATE_NEVER,
        'hydrator_path' => null,
        'hydrators_namespace' => 'DoctrineODMHydrator',
        'auto_generate_hydrators' => AbstractProxyFactory::AUTOGENERATE_NEVER,
        'logger_callable' => null,
        'event_manager' => null,
    ];

    /**
     * Create a Doctrine document manager.
     *
     * @param array $options
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     *
     * @return DocumentManager
     */
    public static function build(array $options)
    {
        $options = array_merge(static::$defaultOptions, $options);

        static::setupAnnotationMetadata($options);

        $config = static::getConfiguration($options);
        static::setupMetadataDriver($config, static::getMetadataDriver($options));
        static::setupDefaultDatabase($config, $options);
        static::setupProxy($config, $options);
        static::setupHydrator($config, $options);
        static::setupLogger($config, $options);

        return DocumentManager::create(
            static::getConnection($config, $options),
            $config,
            $options['event_manager']
        );
    }

    /**
     * Create Doctrine ODM bare configuration.
     *
     * @param array $options
     *
     * @throws \InvalidArgumentException
     *
     * @return Configuration
     */
    protected static function getConfiguration(array $options)
    {
        $cacheDriver = static::getCacheDriver(
            $options['cache_driver'],
            $options['cache_namespace'] ?: 'odm_dc2_' . sha1($options['proxy_path'] ?: sys_get_temp_dir()) . '_'
        );

        $config = new Configuration();
        $config->setMetadataCacheImpl($cacheDriver);

        return $config;
    }

    /**
     * Create metadata driver.
     *
     * @param array $options
     *
     * @throws \RuntimeException
     *
     * @return \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver
     */
    protected static function getMetadataDriver(array $options)
    {
        if ($options['annotation_paths']) {
            return new AnnotationDriver(new AnnotationReader, (array) $options['annotation_paths']);
        }

        if ($options['xml_paths']) {
            return new XmlDriver((array) $options['xml_paths'], '.xml');
        }

        if ($options['yaml_paths']) {
            return new YamlDriver((array) $options['yaml_paths'], '.yml');
        }

        if ($options['php_paths']) {
            return new StaticPHPDriver((array) $options['php_paths']);
        }

        throw new \RuntimeException('No Metadata paths defined');
    }

    /**
     * Setup default database.
     *
     * @param Configuration $config
     * @param array         $options
     */
    protected static function setupDefaultDatabase(Configuration $config, array $options)
    {
        if ($options['default_database']) {
            $config->setDefaultDB($options['default_database']);
        }
    }

    /**
     * Setup hydrators.
     *
     * @param Configuration $config
     * @param array         $options
     */
    protected static function setupHydrator(Configuration $config, array $options)
    {
        $config->setHydratorDir((string) ($options['hydrator_path'] ?: sys_get_temp_dir()));

        $config->setHydratorNamespace((string) $options['hydrators_namespace']);

        $config->setAutoGenerateHydratorClasses((bool) $options['auto_generate_hydrators']);
    }

    /**
     * Setup logger.
     *
     * @param Configuration $config
     * @param array         $options
     */
    protected static function setupLogger(Configuration $config, array $options)
    {
        if ($options['logger_callable']) {
            $config->setLoggerCallable($options['logger_callable']);
        }
    }

    /**
     * Create MongoDB Connection.
     *
     * @param Configuration $config
     * @param array         $options
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     *
     * @return Connection
     */
    protected static function getConnection(Configuration $config, array $options)
    {
        $connection = $options['connection'];

        switch (true) {
            case (is_array($connection)):
                $connection = new Connection(
                    isset($options['connection']['server']) ? $options['connection']['server'] : null,
                    isset($options['connection']['options']) ? $options['connection']['options'] : [],
                    $config,
                    $options['event_manager']
                );
                break;

            case ($connection instanceof Connection):
                if ($options['event_manager'] !== null
                    && $connection->getEventManager() !== $options['event_manager']
                ) {
                    throw new \RuntimeException(
                        'Cannot use different EventManager instances for DocumentManager and Connection.'
                    );
                }
                break;

            default:
                throw new \InvalidArgumentException('Invalid argument: ' . $connection);
        }

        return $connection;
    }
}
