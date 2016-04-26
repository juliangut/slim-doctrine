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
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\Common\Persistence\Mapping\Driver\StaticPHPDriver;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\NamingStrategy;
use Doctrine\ORM\Mapping\QuoteStrategy;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;

/**
 * Doctrine Entity Manager service builder
 */
class EntityManagerBuilder
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
        'naming_strategy' => null,
        'quote_strategy' => null,
        'proxy_path' => null,
        'proxies_namespace' => 'DoctrineORMProxy',
        'auto_generate_proxies' => AbstractProxyFactory::AUTOGENERATE_NEVER,
        'sql_logger' => null,
        'event_manager' => null,
        'custom_types' => [],
        'string_functions' => [],
        'numeric_functions' => [],
        'datetime_functions' => [],
    ];

    /**
     * Create a Doctrine entity manager.
     *
     * @param array $options
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\ORMException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public static function build(array $options)
    {
        $options = array_merge(static::$defaultOptions, $options);

        static::setupAnnotationMetadata($options);

        $config = static::getConfiguration($options);
        static::setupMetadataDriver($config, $options);
        static::setupNamingStrategy($config, $options);
        static::setupQuoteStrategy($config, $options);
        static::setupProxy($config, $options);
        static::setupSQLLogger($config, $options);
        static::setupCustomDQLFunctions($config, $options);

        $entityManager = EntityManager::create(
            $options['connection'],
            $config,
            $options['event_manager']
        );

        $connection = $entityManager->getConnection();
        static::setupCustomDBALTypes($connection, $options);

        return $entityManager;
    }

    /**
     * Create Doctrine ORM bare configuration.
     *
     * @param array $options
     *
     * @throws \InvalidArgumentException
     *
     * @return \Doctrine\ORM\Configuration
     */
    protected static function getConfiguration(array $options)
    {
        $cacheDriver = static::getCacheDriver(
            $options['cache_driver'],
            $options['cache_namespace'] ?: 'orm_dc2_' . sha1($options['proxy_path'] ?: sys_get_temp_dir()) . '_'
        );

        $config = new Configuration();
        $config->setMetadataCacheImpl($cacheDriver);
        $config->setQueryCacheImpl($cacheDriver);
        $config->setResultCacheImpl($cacheDriver);

        return $config;
    }

    /**
     * Create Doctrine ODM configuration.
     *
     * @param \Doctrine\ORM\Configuration $config
     * @param array                       $options
     *
     * @throws \RuntimeException
     */
    protected static function setupMetadataDriver(Configuration $config, array $options)
    {
        $metadataDriver = new MappingDriverChain;
        $metadataDriver->setDefaultDriver(self::getMetadataDriver($config, $options));

        $config->setMetadataDriverImpl($metadataDriver);
    }

    /**
     * @param \Doctrine\ORM\Configuration $config
     * @param array                       $options
     *
     * @throws \RuntimeException
     *
     * @return \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver
     */
    protected static function getMetadataDriver(Configuration $config, array $options)
    {
        if ($options['annotation_paths']) {
            // Only to register ORM annotations mapping
            $config->newDefaultAnnotationDriver();

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
     * Setup naming strategy.
     *
     * @param \Doctrine\ORM\Configuration $config
     * @param array                       $options
     *
     * @throws \InvalidArgumentException
     */
    protected static function setupNamingStrategy(Configuration $config, array $options)
    {
        $namingStrategy = $options['naming_strategy'] ?: new UnderscoreNamingStrategy(CASE_LOWER);
        if (!$namingStrategy instanceof NamingStrategy) {
            throw new \InvalidArgumentException('Naming strategy provided is not valid');
        }

        $config->setNamingStrategy($namingStrategy);
    }

    /**
     * Setup quote strategy.
     *
     * @param \Doctrine\ORM\Configuration $config
     * @param array                       $options
     *
     * @throws \InvalidArgumentException
     */
    protected static function setupQuoteStrategy(Configuration $config, array $options)
    {
        $quoteStrategy = $options['quote_strategy'] ?: new DefaultQuoteStrategy();
        if (!$quoteStrategy instanceof QuoteStrategy) {
            throw new \InvalidArgumentException('Quote strategy provided is not valid');
        }

        $config->setQuoteStrategy($quoteStrategy);
    }

    /**
     * Setup SQL logger.
     *
     * @param \Doctrine\ORM\Configuration $config
     * @param array                       $options
     */
    protected static function setupSQLLogger(Configuration $config, array $options)
    {
        if ($options['sql_logger']) {
            $config->setSQLLogger($options['sql_logger']);
        }
    }

    /**
     * Setup custom DQL functions.
     *
     * @param \Doctrine\ORM\Configuration $config
     * @param array                       $options
     */
    protected static function setupCustomDQLFunctions(Configuration $config, array $options)
    {
        $config->setCustomStringFunctions($options['string_functions']);

        $config->setCustomNumericFunctions($options['numeric_functions']);

        $config->setCustomDatetimeFunctions($options['datetime_functions']);
    }

    /**
     * Setup Custom DBAL types.
     *
     * @param \Doctrine\DBAL\Connection $connection
     * @param array                     $options
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \RuntimeException
     */
    protected static function setupCustomDBALTypes(Connection $connection, array $options)
    {
        $platform = $connection->getDatabasePlatform();

        foreach ($options['custom_types'] as $type => $class) {
            if (Type::hasType($type)) {
                throw new \RuntimeException(sprintf('Type "%s" is already registered', $type));
            }

            Type::addType($type, $class);
            $platform->registerDoctrineTypeMapping($type, $type);
        }
    }
}
