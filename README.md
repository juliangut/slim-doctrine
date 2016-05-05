[![PHP version](https://img.shields.io/badge/PHP-%3E%3D5.5-8892BF.svg?style=flat-square)](http://php.net)
[![Latest Version](https://img.shields.io/packagist/vpre/juliangut/slim-doctrine.svg?style=flat-square)](https://packagist.org/packages/juliangut/slim-doctrine)
[![License](https://img.shields.io/github/license/juliangut/slim-doctrine.svg?style=flat-square)](https://github.com/juliangut/slim-doctrine/blob/master/LICENSE)

[![Build status](https://img.shields.io/travis/juliangut/slim-doctrine.svg?style=flat-square)](https://travis-ci.org/juliangut/slim-doctrine)
[![Style](https://styleci.io/repos/42014429/shield)](https://styleci.io/repos/42014429)
[![Code Quality](https://img.shields.io/scrutinizer/g/juliangut/slim-doctrine.svg?style=flat-square)](https://scrutinizer-ci.com/g/juliangut/slim-doctrine)
[![Code Coverage](https://img.shields.io/coveralls/juliangut/slim-doctrine.svg?style=flat-square)](https://coveralls.io/github/juliangut/slim-doctrine)
[![Total Downloads](https://img.shields.io/packagist/dt/juliangut/slim-doctrine.svg?style=flat-square)](https://packagist.org/packages/juliangut/slim-doctrine)

# Doctrine managers building

Frees you from the tedious work of configuring Doctrine's Entity Manager and MongoDB Document Manager.

> Even though the package was meant for Slim3 integration it can actually be used separately to build Doctrine Managers for any kind of project.

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

### Standalone

```php
use Jgut\Slim\Doctrine\DocumentManagerBuilder;
use Jgut\Slim\Doctrine\EntityManagerBuilder;

$entityManagerSettings = [
    'connection' => [
        'driver' => 'pdo_sqlite',
        'memory' => true,
    ],
    'annotation_paths' => ['path_to_entities_files'],
];
$entityManager = EntityManagerBuilder::build($entityManagerSettings);

$documentManagerSettings = [
    'connection' => [
        'server' => 'mongodb://localhost:27017',
    ],
    'annotation_paths' => ['path_to_documents_files'],
]
$documentManager = DocumentManagerBuilder::build($documentManagerSettings);
```

### In Slim3

Register in the DI container as any other service.

```php
use Jgut\Slim\Doctrine\EntityManagerBuilder;
use Slim\App;

// Create Slim app and fetch DI Container
$app = new App();
$container = $app->getContainer();

// Register Entity Manager in the container
$container['entityManager'] = function () {
    $entityManagerSettings = [
        'connection' => [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ],
        'annotation_paths' => ['path_to_entities_files'],
    ];

    return EntityManagerBuilder::build($entityManagerSettings);
};

$app->get('/', function () {
    $this->entityManager->persist(new \Entity);
    $this->entityManager->flush();
});
```

You can use Slim3 settings service to store Doctrine configurations.

```php
use Jgut\Slim\Doctrine\DocumentManagerBuilder;
use Interop\Container\ContainerInterface;
use Slim\App;

$settings = [
    'settings' => [
        'document_manager' => [
            'connection' => [
                'server' => 'mongodb://localhost:27017',
            ],
            'annotation_paths' => ['path_to_documents_files'],
        ],
    ],
];

// Create Slim app and fetch DI Container
$app = new App($settings);
$container = $app->getContainer();

// Register Document Manager in the container
$container['documentManager'] = function (ContainerInterface $container) {
    return DocumentManagerBuilder::build($container->get('settings')['document_manager']);
};

$app->get('/', function () {
    $this->documentManager->persist(new \Document);
    $this->documentManager->flush();
});
```

### ORM Entity Manager configurations

* `connection` array of PDO configurations or \Doctrine\DBAL\Connection
* `cache_driver` \Doctrine\Common\Cache\Cache
* `cache_namespace` string for cache namespace
* `annotation_files` array of Doctrine annotations files
* `annotation_namespaces` array of Doctrine annotations namespaces
* `annotation_autoloaders` array of Doctrine annotations autoloader callables
* `annotation_paths` array of paths where to find annotated entity files
* `xml_paths` array of paths where to find XML entity mapping files
* `yaml_paths` array of paths where to find YAML entity mapping files
* `php_paths` array of paths where to find PHP entity mapping files
* `naming_strategy` a `\Doctrine\ORM\Mapping\NamingStrategy`, defaults to `UnderscoreNamingStrategy`
* `quote_strategy` a `\Doctrine\ORM\Mapping\QuoteStrategy`, defaults to `DefaultQuoteStrategy`
* `proxy_path` path were Doctrine creates its proxy classes, defaults to /tmp
* `proxies_namespace` string for proxies namespace, defaults to 'DoctrineORMProxy'
* `auto_generate_proxies` integer indicating proxy auto generation behavior
* `sql_logger` a `\Doctrine\DBAL\Logging\SQLLogger`
* `event_manager` a configured `Doctrine\Common\EventManager`
* `custom_types` array of `'type_name' => '\Doctrine\DBAL\Types\Type'`
* `string_functions` array of custom `'function_name' => '\Doctrine\ORM\Query\AST\Functions\FunctionNode'` DQL functions
* `numeric_functions` array of custom `'function_name' => '\Doctrine\ORM\Query\AST\Functions\FunctionNode'` DQL functions
* `datetime_functions` array of custom `'function_name' => '\Doctrine\ORM\Query\AST\Functions\FunctionNode'` DQL functions

### ODM Document Manager configurations

* `connection` array of MongoClient configurations or \Doctrine\MongoDB\Connection
* `cache_driver` \Doctrine\Common\Cache\Cache
* `cache_namespace` string for cache namespace
* `annotation_files` array of Doctrine annotations files
* `annotation_namespaces` array of Doctrine annotations namespaces
* `annotation_autoloaders` array of Doctrine annotations autoloader callables
* `annotation_paths` array of paths where to find annotated document files
* `xml_paths` array of paths where to find XML document mapping files
* `yaml_paths` array of paths where to find YAML document mapping files
* `php_paths` array of paths where to find PHP document mapping files
* `default_database` default database to be used in case none specified
* `proxy_path` path where Doctrine creates its proxy classes, defaults to /tmp
* `proxies_namespace` string for proxies namespace, defaults to 'DoctrineODMProxy'
* `auto_generate_proxies` integer indicating proxy auto generation behavior
* `hydrator_path` path where Doctrine creates its hydrator classes, defaults to /tmp
* `hydrators_namespace` string for hydrators namespace, defaults to 'DoctrineODMHydrator'
* `auto_generate_hydrators` integer indicating hydrators auto generation behavior
* `logger_callable` valid callable
* `event_manager` a configured `Doctrine\Common\EventManager`

## Considerations

These are general considerations when configuring both Entity and Document managers:

* `connection` configuration is mandatory:
    * For ORM as a PDO configurations array or as a proper Doctrine DBAL Connection.
    * For ODM as a MongoClient configurations array or as a proper Doctrine MongoDB Connection.

* One of 'paths' configurations is mandatory (`annotation_paths`, `xml_paths`, `yaml_paths` or `php_paths`) as it's needed by Doctrine to configure the Metadata Driver. They are checked in that order and the first to appear is the one that gets configured. Most commonly used is annotation_paths.

* `auto_generate_proxies` configuration values are `Doctrine\Common\Proxy\AbstractProxyFactory` constants, in both cases it defaults to `Doctrine\Common\Proxy\AbstractProxyFactory::AUTOGENERATE_NEVER` (0).

* Managers are being configured **ready for production**, this mainly means proxies and hydrators won't be automatically generated and, in case no `cache_driver` is provided, one will be auto-generated in the following order depending on availability: `ApcCache`, `XcacheCache`, `MemcacheCache`, `RedisCache` and finally fallback to `ArrayCache` which is always available. It is recommended you always provide your cache provider, for development you should use `VoidCache`.

## CLI Application builder

The easiest way to create `cli-config.php` for Doctrine CLI command runner

```php
require __DIR__ . '/vendor/autoload.php';

use Jgut\Slim\Doctrine\CLIApplicationBuilder;

$CLISettings = [
    'cache_driver' => new VoidCache,
];
$settings = require 'configurations.php';

$application = CLIApplication::build(
    array_merge($settings['entity_manager'], $CLISettings),
    array_merge($settings['document_manager'], $CLISettings)
);

return $application->run();
```

## Contributing

Found a bug or have a feature request? [Please open a new issue](https://github.com/juliangut/slim-doctrine/issues). Have a look at existing issues before

See file [CONTRIBUTING.md](https://github.com/juliangut/slim-doctrine/blob/master/CONTRIBUTING.md)

### Contributors

* [@mcrauwel (Matthias Crauwels)](https://github.com/mcrauwel)

## License

See file [LICENSE](https://github.com/juliangut/slim-doctrine/blob/master/LICENSE) included with the source code for a copy of the license terms

