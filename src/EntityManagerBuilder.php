<?php
/**
 * Slim3 Doctrine integration (https://github.com/juliangut/slim-doctrine)
 *
 * @link https://github.com/juliangut/slim-doctrine for the canonical source repository
 * @license https://raw.githubusercontent.com/juliangut/slim-doctrine/master/LICENSE
 */

namespace Jgut\Slim\Doctrine;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\Mapping\NamingStrategy;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;

/**
 * Doctrine Entity Manager service builder
 */
class EntityManagerBuilder
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @param array $options
     * @throws \InvalidArgumentException
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public static function build(array $options = [])
    {
        $proxyDir = self::getOption($options, 'proxy_path');
        $cache = self::getOption($options, 'cache_driver');

        if ($cache !== null && !$cache instanceof Cache) {
            throw new \InvalidArgumentException('Cache Driver provided is not valid');
        }

        $config = Setup::createConfiguration(false, $proxyDir, $cache);

        self::setupNamingStrategy($config, $options);

        self::setupAnnotationMetadata($options);

        if (!self::setupMetadataDriver($config, $options)) {
            throw new \InvalidArgumentException('No Metadata Driver defined');
        }

        self::setupProxy($config, $options);

        return EntityManager::create(self::getOption($options, 'connection'), $config);
    }

    /**
     * @param \Doctrine\ORM\Configuration $config
     * @param array $options
     * @throws \InvalidArgumentException
     */
    protected static function setupNamingStrategy(Configuration &$config, array $options = [])
    {
        $namingStrategy = self::getOption($options, 'naming_strategy');
        $namingStrategy = $namingStrategy ?: new UnderscoreNamingStrategy();
        if (!$namingStrategy instanceof NamingStrategy) {
            throw new \InvalidArgumentException('Naming strategy provided is not valid');
        }

        $config->setNamingStrategy($namingStrategy);
    }

    /**
     * Set up annotation metadata
     *
     * @param array $options
     */
    protected static function setupAnnotationMetadata(array $options = [])
    {
        $annotationFiles = self::getOption($options, 'annotation_files');
        if ($annotationFiles) {
            foreach ($annotationFiles as $file) {
                AnnotationRegistry::registerFile($file);
            }
        }

        $annotationNamespaces = self::getOption($options, 'annotation_namespaces');
        if ($annotationNamespaces) {
            AnnotationRegistry::registerAutoloadNamespaces($annotationNamespaces);
        }

        $annotationAuloaders = self::getOption($options, 'annotation_autoloaders');
        if ($annotationAuloaders) {
            foreach ($annotationAuloaders as $autoloader) {
                AnnotationRegistry::registerLoader($autoloader);
            }
        }
    }

    /**
     * Set up annotation metadata
     *
     * @param \Doctrine\ORM\Configuration $config
     * @param array $options
     *
     * @return bool
     */
    protected static function setupMetadataDriver(Configuration &$config, array $options = [])
    {
        $annotationPaths = self::getOption($options, 'annotation_paths');
        if ($annotationPaths) {
            $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver($annotationPaths, false));
        }

        $xmlPaths = self::getOption($options, 'xml_paths');
        if ($xmlPaths) {
            $config->setMetadataDriverImpl(new XmlDriver($xmlPaths));
        }

        $yamlPaths = self::getOption($options, 'yaml_paths');
        if ($yamlPaths) {
            $config->setMetadataDriverImpl(new YamlDriver($yamlPaths));
        }

        return $annotationPaths || $xmlPaths || $yamlPaths;
    }

    /**
     * Set up proxies
     *
     * @param \Doctrine\ORM\Configuration $config
     * @param array $options
     */
    protected static function setupProxy(Configuration &$config, array $options = [])
    {
        $proxiesNamespace = self::getOption($options, 'proxies_namespace');
        if ($proxiesNamespace) {
            $config->setProxyNamespace($proxiesNamespace);
        }

        $config->setAutoGenerateProxyClasses((bool) self::getOption($options, 'auto_generate_proxies', false));
    }

    /**
     * Get option value or default if none existent
     *
     * @param array $options
     * @param string $option
     * @param mixed $default
     *
     * @return mixed
     */
    protected static function getOption($options, $option, $default = null)
    {
        return isset($options[$option]) ? $options[$option] : $default;
    }
}
