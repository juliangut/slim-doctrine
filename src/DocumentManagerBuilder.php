<?php
/**
 * Slim3 Doctrine integration (https://github.com/juliangut/slim-doctrine)
 *
 * @link https://github.com/juliangut/slim-doctrine for the canonical source repository
 *
 * @license https://raw.githubusercontent.com/juliangut/slim-doctrine/master/LICENSE
 */

namespace Jgut\Slim\Doctrine;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
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
    /**
     * Default configuration options.
     *
     * @var array
     */
    protected static $defaultOptions = [
        'connection' => null,
        'cache_driver' => null,
        'annotation_files' => [],
        'annotation_namespaces' => [],
        'annotation_autoloaders' => [],
        'annotation_paths' => null,
        'xml_paths' => null,
        'yaml_paths' => null,
        'default_database' => null,
        'proxy_path' => null,
        'proxies_namespace' => null,
        'auto_generate_proxies' => AbstractProxyFactory::AUTOGENERATE_NEVER,
        'hydrator_path' => null,
        'hydrators_namespace' => null,
        'logger_callable' => null,
        'event_manager' => null,
    ];

    /**
     * Create a Doctrine document manager.
     *
     * @param array $options
     *
     * @throws \InvalidArgumentException
     *
     * @return \Doctrine\ODM\MongoDB\DocumentManager
     */
    public static function build(array $options)
    {
        $options = array_merge(static::$defaultOptions, $options);

        if ($options['cache_driver'] !== null && !$options['cache_driver'] instanceof Cache) {
            throw new \InvalidArgumentException('Cache Driver provided is not valid');
        }

        static::setupAnnotationMetadata($options);

        $config = static::createConfiguration($options);
        if (!$config instanceof Configuration) {
            throw new \InvalidArgumentException('No Metadata Driver defined');
        }

        static::setupDefaultDatabase($config, $options);

        static::setupProxy($config, $options);

        static::setupHydrator($config, $options);

        static::setupLogger($config, $options);

        return DocumentManager::create(self::getConnection($options), $config, $options['event_manager']);
    }

    /**
     * Set up annotation metadata.
     *
     * @param array $options
     */
    protected static function setupAnnotationMetadata(array $options)
    {
        AnnotationDriver::registerAnnotationClasses();

        foreach ($options['annotation_files'] as $file) {
            AnnotationRegistry::registerFile($file);
        }

        AnnotationRegistry::registerAutoloadNamespaces($options['annotation_namespaces']);

        foreach ($options['annotation_autoloaders'] as $autoloader) {
            AnnotationRegistry::registerLoader($autoloader);
        }
    }

    /**
     * Create Doctrine ODM configuration.
     *
     * @param array $options
     *
     * @return \Doctrine\ODM\MongoDB\Configuration|null
     */
    protected static function createConfiguration(array $options)
    {
        $config = static::createBareConfiguration($options);

        if ($options['annotation_paths']) {
            $config->setMetadataDriverImpl(
                $config->newDefaultAnnotationDriver(static::normalizePaths($options['annotation_paths']), false)
            );
        }

        if ($options['xml_paths']) {
            $config->setMetadataDriverImpl(new XmlDriver(static::normalizePaths($options['xml_paths'])));
        }

        if ($options['yaml_paths']) {
            $config->setMetadataDriverImpl(new YamlDriver(static::normalizePaths($options['yaml_paths'])));
        }

        return $config->getMetadataDriverImpl() !== null ? $config : null;
    }

    /**
     * Create Doctrine ODM bare configuration.
     *
     * @param array $options
     *
     * @return \Doctrine\ODM\MongoDB\Configuration
     */
    protected static function createBareConfiguration(array $options)
    {
        $proxyDir = $options['proxy_path'] ?: sys_get_temp_dir();

        $cache = static::getCacheDriver($options);
        if ($cache instanceof CacheProvider) {
            $cache->setNamespace('odm_dc2_' . md5($proxyDir) . '_');
        }

        $config = new Configuration();
        $config->setMetadataCacheImpl($cache);
        $config->setProxyDir($proxyDir);
        $config->setProxyNamespace('DoctrineODMProxy');
        $config->setHydratorDir($options['hydrator_path'] ?: sys_get_temp_dir());
        $config->setHydratorNamespace('DoctrineODMHydrator');

        return $config;
    }

    /**
     * Get cache driver.
     *
     * @param array $options
     *
     * @return \Doctrine\Common\Cache\Cache
     */
    protected static function getCacheDriver(array $options)
    {
        $cache = $options['cache_driver'];

        if ($cache === null) {
            // @codeCoverageIgnoreStart
            if (extension_loaded('apc')) {
                $cache = new \Doctrine\Common\Cache\ApcCache();
            } elseif (extension_loaded('xcache')) {
                $cache = new \Doctrine\Common\Cache\XcacheCache();
            } elseif (extension_loaded('memcache')) {
                $memcache = new \Memcache();
                $memcache->connect('127.0.0.1');
                $cache = new \Doctrine\Common\Cache\MemcacheCache();
                $cache->setMemcache($memcache);
            } elseif (extension_loaded('redis')) {
                $redis = new \Redis();
                $redis->connect('127.0.0.1');
                $cache = new \Doctrine\Common\Cache\RedisCache();
                $cache->setRedis($redis);
            } else {
                $cache = new \Doctrine\Common\Cache\ArrayCache();
            }
            // @codeCoverageIgnoreEnd
        }

        return $cache;
    }

    /**
     * Normalize paths to array.
     *
     * @param array|string $paths
     *
     * @return array
     */
    protected static function normalizePaths($paths)
    {
        return is_array($paths) ? $paths : [$paths];
    }

    /**
     * Setup default database.
     *
     * @param \Doctrine\ODM\MongoDB\Configuration $config
     * @param array                               $options
     */
    protected static function setupDefaultDatabase(Configuration &$config, array $options)
    {
        if ($options['default_database']) {
            $config->setDefaultDB($options['default_database']);
        }
    }

    /**
     * Setup proxies.
     *
     * @param \Doctrine\ODM\MongoDB\Configuration $config
     * @param array                               $options
     */
    protected static function setupProxy(Configuration &$config, array $options)
    {
        if ($options['proxies_namespace']) {
            $config->setProxyNamespace($options['proxies_namespace']);
        }

        $config->setAutoGenerateProxyClasses(intval($options['auto_generate_proxies']));
    }

    /**
     * Setup hydrators.
     *
     * @param \Doctrine\ODM\MongoDB\Configuration $config
     * @param array                               $options
     */
    protected static function setupHydrator(Configuration &$config, array $options)
    {
        if ($options['hydrators_namespace']) {
            $config->setHydratorNamespace($options['hydrators_namespace']);
        }
    }

    /**
     * Setup logger.
     *
     * @param \Doctrine\ODM\MongoDB\Configuration $config
     * @param array                               $options
     */
    protected static function setupLogger(Configuration &$config, array $options)
    {
        if ($options['logger_callable']) {
            $config->setLoggerCallable($options['logger_callable']);
        }
    }

    /**
     * Create MongoDB Connection.
     *
     * @param array $options
     *
     * @throws \InvalidArgumentException
     *
     * @return \Doctrine\MongoDB\Connection
     */
    protected static function getConnection(array $options)
    {
        $connection = $options['connection'];

        if (!$connection instanceof Connection) {
            if (!is_array($connection)) {
                throw new \InvalidArgumentException(sprintf('Invalid argument: %s', $connection));
            }

            $connection = new Connection(
                isset($options['connection']['server']) ? $options['connection']['server'] : null,
                isset($options['connection']['options']) ? $options['connection']['options'] : []
            );
        }

        return $connection;
    }
}
