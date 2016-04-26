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
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\MemcacheCache;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\Common\Cache\XcacheCache;

/**
 * Doctrine Object Manager commons.
 */
trait ObjectManagerTrait
{
    /**
     * @param \Doctrine\Common\Cache\CacheProvider|null $cacheDriver
     * @param string                                    $cacheNamespace
     *
     * @throws \InvalidArgumentException
     *
     * @return \Doctrine\Common\Cache\CacheProvider
     */
    protected static function getCacheDriver($cacheDriver, $cacheNamespace)
    {
        if ($cacheDriver === null) {
            // @codeCoverageIgnoreStart
            if (extension_loaded('apc')) {
                $cacheDriver = new ApcuCache;
            } elseif (extension_loaded('xcache')) {
                $cacheDriver = new XcacheCache;
            } elseif (extension_loaded('memcache')) {
                $memcache = new \Memcache;
                $memcache->connect('127.0.0.1');

                $cacheDriver = new MemcacheCache;
                $cacheDriver->setMemcache($memcache);
            } elseif (extension_loaded('redis')) {
                $redis = new \Redis();
                $redis->connect('127.0.0.1');

                $cacheDriver = new RedisCache;
                $cacheDriver->setRedis($redis);
            } else {
                $cacheDriver = new ArrayCache;
            }
            // @codeCoverageIgnoreEnd
        }

        if (!$cacheDriver instanceof CacheProvider) {
            throw new \InvalidArgumentException('Cache Driver provided is not valid');
        }

        $cacheDriver->setNamespace(trim($cacheNamespace) !== '' ? trim($cacheNamespace) : 'dc2_' . sha1(time()) . '_');

        return $cacheDriver;
    }

    /**
     * Set up annotation metadata.
     *
     * @param array $options
     *
     * @throws \InvalidArgumentException
     */
    protected static function setupAnnotationMetadata(array $options)
    {
        foreach ($options['annotation_files'] as $file) {
            AnnotationRegistry::registerFile($file);
        }

        AnnotationRegistry::registerAutoloadNamespaces($options['annotation_namespaces']);

        foreach ($options['annotation_autoloaders'] as $autoLoader) {
            AnnotationRegistry::registerLoader($autoLoader);
        }
    }

    /**
     * Setup proxies.
     *
     * @param \Doctrine\ORM\Configuration|\Doctrine\ODM\MongoDB\Configuration $config
     * @param array                                                           $options
     *
     * @throws \InvalidArgumentException
     */
    protected static function setupProxy($config, array $options)
    {
        $config->setProxyDir((string) ($options['proxy_path'] ?: sys_get_temp_dir()));

        $config->setProxyNamespace((string) $options['proxies_namespace']);

        $config->setAutoGenerateProxyClasses((int) $options['auto_generate_proxies']);
    }
}
