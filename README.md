[![Latest Version](https://img.shields.io/packagist/vpre/juliangut/slim-doctrine.svg?style=flat-square)](https://packagist.org/packages/juliangut/slim-doctrine)
[![License](https://img.shields.io/github/license/juliangut/slim-doctrine.svg?style=flat-square)](https://github.com/juliangut/slim-doctrine/blob/master/LICENSE)

[![Build status](https://img.shields.io/travis/juliangut/slim-doctrine.svg?style=flat-square)](https://travis-ci.org/juliangut/slim-doctrine)
[![Code Quality](https://img.shields.io/scrutinizer/g/juliangut/slim-doctrine.svg?style=flat-square)](https://scrutinizer-ci.com/g/juliangut/slim-doctrine)
[![Code Coverage](https://img.shields.io/coveralls/juliangut/slim-doctrine.svg?style=flat-square)](https://coveralls.io/github/juliangut/slim-doctrine)
[![Total Downloads](https://img.shields.io/packagist/dt/juliangut/slim-doctrine.svg?style=flat-square)](https://packagist.org/packages/juliangut/slim-doctrine)

# Slim3 Doctrine integration

Doctrine handler service for Slim3.

## Installation

Best way to install is using [Composer](https://getcomposer.org/):

```
composer require juliangut/slim-doctrine
```

Then require_once the autoload file:

```php
require_once './vendor/autoload.php';
```

## Usage

Register in the DI container as any other service.

```php
use Jgut\Slim\Doctrine\EntityManagerBuilder;
use Slim\App;

// Create Slim app and fetch DI Container
$app = new App();
$container = $app->getContainer();

// Register Entity Manager in the container
$container['entityManager'] = function () {
    $doctrineSettings = [
        'connection' => [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ],
        'annotation_paths' => ['path_to_entities_files'],
    ];

    return EntityManagerBuilder::build($doctrineSettings);
};

$app->get('/', function () {
    // Use entity manager
    $this->entityManager->beginTransaction();
    // Do your magic
    $this->entityManager->commit();
});
```

You can use Slim settings service to store Doctrine configurations.

```php
use Jgut\Slim\Doctrine\EntityManagerBuilder;
use Interop\Container\ContainerInterface;
use Slim\App;

$settings = [
    'settings' => [
        'doctrine' => [
            'connection' => [
                'driver' => 'pdo_sqlite',
                'memory' => true,
            ],
            'annotation_paths' => ['path_to_entities_files'],
        ],
    ],
];

// Create Slim app and fetch DI Container
$app = new App($settings);
$container = $app->getContainer();

// Register Entity Manager in the container
$container['entityManager'] = function (ContainerInterface $container) {
    return EntityManagerBuilder::build($container->get('settings')['doctrine']);
};
```

### Available configurations

* `connection` array of PDO configurations or \Doctrine\DBAL\Connection
* `cache_driver` \Doctrine\Common\Cache\Cache
* `annotation_files` array of Doctrine annotations files
* `annotation_namespaces` array of Doctrine annotations namespaces
* `annotation_autoloaders` array of Doctrine annotations autoloader callables
* `annotation_paths` array of paths where to find annotated entity files
* `xml_paths` array of paths where to find XML entity mapping files
* `yaml_paths` array of paths where to find YAML entity mapping files
* `proxy_path` path were Doctrine creates its proxy classes, defaults to /tmp
* `proxies_namespace` string for proxies namespace, defaults to 'DoctrineProxies'
* `auto_generate_proxies` integer indicating proxy auto generation behavior. Values are `Doctrine\Common\Proxy\AbstractProxyFactory` constants, defaults to `AUTOGENERATE_NEVER` (0)
* `naming_strategy` \Doctrine\ORM\Mapping\NamingStrategy, defaults to `UnderscoreNamingStrategy`
* `sql_logger` \Doctrine\DBAL\Logging\SQLLogger

### Considerations

`connection` configuration is mandatory either as a configurations array or as a proper Doctrine DBAL Connection

One of `annotation_paths`, `xml_paths` or `yaml_paths` is mandatory as it's needed by Doctrine to include a Metadata Driver. Most commonly used is annotation_paths.

Doctrine is being configured **ready for production** and not for development, this mainly means proxies won't be automatically generated and, in case no `cache_driver` was provided, Doctrine will use an auto-generated cache driver in the following order depending on availability: `ApcCache`, `XcacheCache`, `MemcacheCache`, `RedisCache`, and finally fall back to `ArrayCache` which is always available

## Contributing

Found a bug or have a feature request? [Please open a new issue](https://github.com/juliangut/slim-doctrine/issues). Have a look at existing issues before

See file [CONTRIBUTING.md](https://github.com/juliangut/slim-doctrine/blob/master/CONTRIBUTING.md)

### Contributors

* [@fousheezy (John Foushee)](https://github.com/fousheezy)
* [@mcrauwel (Matthias Crauwels)](https://github.com/mcrauwel)
* [@mgersten (Micah Gersten)](https://github.com/mgersten)

## License

### Release under BSD-3-Clause License.

See file [LICENSE](https://github.com/juliangut/slim-doctrine/blob/master/LICENSE) included with the source code for a copy of the license terms

