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
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\ORM\Mapping\Driver\StaticPHPDriver;
use Doctrine\ORM\Mapping\NamingStrategy;
use Doctrine\ORM\Mapping\QuoteStrategy;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Tools\Setup;

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
        'cache_driver' => null,
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
        'proxies_namespace' => null,
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
     * @throws \InvalidArgumentException
     *
     * @return \Doctrine\ORM\EntityManager
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

        static::setupNamingStrategy($config, $options);

        static::setupQuoteStrategy($config, $options);

        static::setupProxy($config, $options);

        static::setupSQLLogger($config, $options);

        static::setupCustomDQLFunctions($config, $options);

        $entityManager = EntityManager::create($options['connection'], $config, $options['event_manager']);
        $connection = $entityManager->getConnection();

        static::setupCustomDBALTypes($connection, $options);

        return $entityManager;
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

        if ($options['php_paths']) {
            $config = Setup::createConfiguration(
                false,
                $options['proxy_path'],
                $options['cache_driver']
            );
            $config->setMetadataDriverImpl(new StaticPHPDriver(static::normalizePaths($options['php_paths'])));

            return $config;
        }

        return null;
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
    protected static function setupQuoteStrategy(Configuration &$config, array $options)
    {
        $quoteStrategy = $options['quote_strategy'] ?: new DefaultQuoteStrategy();
        if (!$quoteStrategy instanceof QuoteStrategy) {
            throw new \InvalidArgumentException('Quote strategy provided is not valid');
        }

        $config->setQuoteStrategy($quoteStrategy);
    }

    /**
     * Setup proxies.
     *
     * @param \Doctrine\ORM\Configuration $config
     * @param array                       $options
     */
    protected static function setupProxy(Configuration &$config, array $options)
    {
        $proxiesNamespace = $options['proxies_namespace'] ? $options['proxies_namespace'] : 'DoctrineORMProxy';
        $config->setProxyNamespace((string) $proxiesNamespace);

        $config->setAutoGenerateProxyClasses((int) $options['auto_generate_proxies']);
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

    /**
     * Setup custom DQL functions.
     *
     * @param \Doctrine\ORM\Configuration $config
     * @param array                       $options
     */
    protected static function setupCustomDQLFunctions(Configuration &$config, array $options)
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
     */
    protected static function setupCustomDBALTypes(Connection &$connection, array $options)
    {
        foreach ($options['custom_types'] as $name => $class) {
            Type::addType($name, $class);
            $connection->getDatabasePlatform()->registerDoctrineTypeMapping($name, $name);
        }
    }
}
