<?php
/**
 * Slim3 Doctrine integration (https://github.com/juliangut/slim-doctrine)
 *
 * @link https://github.com/juliangut/slim-doctrine for the canonical source repository
 *
 * @license https://raw.githubusercontent.com/juliangut/slim-doctrine/master/LICENSE
 */

namespace Jgut\Slim\Doctrine;

use InvalidArgumentException;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\Mapping\NamingStrategy;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\Common\Proxy\AbstractProxyFactory;

/**
 * Doctrine Entity Manager service builder
 */
class EntityManagerBuilder
{
    /**
     * Default configuration options.
     *
     * @var array
     */
    protected static $defaultOptions = [
        'connection' => null,
        'proxy_path' => null,
        'cache_driver' => null,
        'annotation_files' => [],
        'annotation_namespaces' => [],
        'annotation_autoloaders' => [],
        'annotation_paths' => null,
        'xml_paths' => null,
        'yaml_paths' => null,
        'naming_strategy' => null, //\Doctrine\ORM\Mapping\UnderscoreNamingStrategy
        'proxies_namespace' => null,
        'auto_generate_proxies' => AbstractProxyFactory::AUTOGENERATE_NEVER,
        'sql_logger' => null,
    ];

    /**
     * Create a Doctrine entity manager.
     *
     * @param array $options
     *
     * @throws \InvalidArgumentException
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public static function build(array $options)
    {
        $options = array_merge(static::$defaultOptions, $options);

        if ($options['cache_driver'] !== null && !$options['cache_driver'] instanceof Cache) {
            throw new InvalidArgumentException('Cache Driver provided is not valid');
        }

        static::setupAnnotationMetadata($options);

        $config = static::createConfiguration($options);
        if (!$config instanceof Configuration) {
            throw new InvalidArgumentException('No Metadata Driver defined');
        }

        static::setupNamingStrategy($config, $options);

        static::setupProxy($config, $options);

        static::setupSQLLogger($config, $options);

        return EntityManager::create($options['connection'], $config);
    }

    /**
     * Set up annotation metadata.
     *
     * @param array $options
     */
    protected static function setupAnnotationMetadata(array $options)
    {
        foreach ($options['annotation_files'] as $file) {
            AnnotationRegistry::registerFile($file);
        }

        AnnotationRegistry::registerAutoloadNamespaces($options['annotation_namespaces']);

        foreach ($options['annotation_autoloaders'] as $autoloader) {
            AnnotationRegistry::registerLoader($autoloader);
        }
    }

    /**
     * Create Doctrine configuration.
     *
     * @param array $options
     *
     * @return \Doctrine\ORM\Configuration|null
     */
    protected static function createConfiguration(array $options)
    {
        if ($options['annotation_paths']) {
            return Setup::createAnnotationMetadataConfiguration(
                static::normalizePaths($options['annotation_paths']),
                false,
                $options['proxy_path'],
                $options['cache_driver'],
                false
            );
        }

        if ($options['xml_paths']) {
            return Setup::createXMLMetadataConfiguration(
                static::normalizePaths($options['xml_paths']),
                false,
                $options['proxy_path'],
                $options['cache_driver']
            );
        }

        if ($options['yaml_paths']) {
            return Setup::createYAMLMetadataConfiguration(
                static::normalizePaths($options['yaml_paths']),
                false,
                $options['proxy_path'],
                $options['cache_driver']
            );
        }

        return null;
    }

    /**
     * Normalize paths to array.
     *
     * @param array|string $paths
     * @return array
     */
    protected static function normalizePaths($paths)
    {
        return is_array($paths) ? $paths : [$paths];
    }

    /**
     * Setup naming strategy.
     *
     * @param \Doctrine\ORM\Configuration $config
     * @param array                       $options
     *
     * @throws \InvalidArgumentException
     */
    protected static function setupNamingStrategy(Configuration &$config, array $options)
    {
        $namingStrategy = $options['naming_strategy'] ?: new UnderscoreNamingStrategy(CASE_LOWER);
        if (!$namingStrategy instanceof NamingStrategy) {
            throw new InvalidArgumentException('Naming strategy provided is not valid');
        }

        $config->setNamingStrategy($namingStrategy);
    }

    /**
     * Setup proxies.
     *
     * @param \Doctrine\ORM\Configuration $config
     * @param array                       $options
     */
    protected static function setupProxy(Configuration &$config, array $options)
    {
        if ($options['proxies_namespace']) {
            $config->setProxyNamespace($options['proxies_namespace']);
        }

        $config->setAutoGenerateProxyClasses(intval($options['auto_generate_proxies']));
    }

    /**
     * Setup SQL logger.
     *
     * @param \Doctrine\ORM\Configuration $config
     * @param array                       $options
     */
    protected static function setupSQLLogger(Configuration &$config, array $options)
    {
        if ($options['sql_logger']) {
            $config->setSQLLogger($options['sql_logger']);
        }
    }
}
