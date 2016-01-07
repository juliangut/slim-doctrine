[![Latest Version](https://img.shields.io/packagist/vpre/juliangut/slim-doctrine.svg?style=flat-square)](https://packagist.org/packages/juliangut/slim-doctrine)
[![License](https://img.shields.io/github/license/juliangut/slim-doctrine.svg?style=flat-square)](https://github.com/juliangut/slim-doctrine/blob/master/LICENSE)

[![Build status](https://img.shields.io/travis/juliangut/slim-doctrine.svg?style=flat-square)](https://travis-ci.org/juliangut/slim-doctrine)
[![Style](https://styleci.io/repos/42014429/shield)](https://styleci.io/repos/42014429)
[![Code Quality](https://img.shields.io/scrutinizer/g/juliangut/slim-doctrine.svg?style=flat-square)](https://scrutinizer-ci.com/g/juliangut/slim-doctrine)
[![Code Coverage](https://img.shields.io/coveralls/juliangut/slim-doctrine.svg?style=flat-square)](https://coveralls.io/github/juliangut/slim-doctrine)
[![Total Downloads](https://img.shields.io/packagist/dt/juliangut/slim-doctrine.svg?style=flat-square)](https://packagist.org/packages/juliangut/slim-doctrine)

# Slim3 Doctrine integration

Doctrine handler service for Slim3.

Frees you from the tedious work of configuring Doctrine's Entity Manager and Document Manager.

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
    $this->entityManager->persist(new \Entity);
    $this->entityManager->flush();
});
```

You can use Slim settings service to store Doctrine configurations.

```php
use Jgut\Slim\Doctrine\DocumentManagerBuilder;
use Interop\Container\ContainerInterface;
use Slim\App;

$settings = [
    'settings' => [
        'doctrine' => [
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
    return DocumentManagerBuilder::build($container->get('settings')['doctrine']);
};

$app->get('/', function () {
    $this->documentManager->persist(new \Document);
    $this->documentManager->flush();
});
```

### ORM Entity Manager configurations

* `connection` array of PDO configurations or \Doctrine\DBAL\Connection
* `cache_driver` \Doctrine\Common\Cache\Cache
* `annotation_files` array of Doctrine annotations files
* `annotation_namespaces` array of Doctrine annotations namespaces
* `annotation_autoloaders` array of Doctrine annotations autoloader callables
* `annotation_paths` array of paths where to find annotated entity files
* `xml_paths` array of paths where to find XML entity mapping files
* `yaml_paths` array of paths where to find YAML entity mapping files
* `naming_strategy` a `\Doctrine\ORM\Mapping\NamingStrategy`, defaults to `UnderscoreNamingStrategy`
* `proxy_path` path were Doctrine creates its proxy classes, defaults to /tmp
* `proxies_namespace` string for proxies namespace, defaults to 'DoctrineORMProxy'
* `auto_generate_proxies` integer indicating proxy auto generation behavior
* `sql_logger` a `\Doctrine\DBAL\Logging\SQLLogger`
* `event_manager` a configured `Doctrine\Common\EventManager`

### ODM Document Manager configurations

* `connection` array of MongoClient configurations or \Doctrine\MongoDB\Connection
* `cache_driver` \Doctrine\Common\Cache\Cache
* `annotation_files` array of Doctrine annotations files
* `annotation_namespaces` array of Doctrine annotations namespaces
* `annotation_autoloaders` array of Doctrine annotations autoloader callables
* `annotation_paths` array of paths where to find annotated document files
* `xml_paths` array of paths where to find XML document mapping files
* `yaml_paths` array of paths where to find YAML document mapping files
* `default_database` default database to be used in case none specified
* `proxy_path` path where Doctrine creates its proxy classes, defaults to /tmp
* `proxies_namespace` string for proxies namespace, defaults to 'DoctrineODMProxy'
* `auto_generate_proxies` integer indicating proxy auto generation behavior
* `hydrator_path` path where Doctrine creates its hydrator classes, defaults to /tmp
* `hydrators_namespace` string for hydrators namespace, defaults to 'DoctrineODMHydrator'
* `logger_callable` valid callable
* `event_manager` a configured `Doctrine\Common\EventManager`

## Considerations

These are general considerations when configuring both Entity and Document managers:

* `connection` configuration is mandatory:
    * For ORM as a PDO configurations array or as a proper Doctrine DBAL Connection.
    * For ODM as a MongoClient configurations array or as a proper Doctrine MongoDB Connection.

* One of 'path' configurations is mandatory ( `annotation_paths`, `xml_paths` or `yaml_paths`) as it's needed by Doctrine to configure the Metadata Driver. They are checked in that order and the first to appear is the one that gets configured. Most commonly used is annotation_paths.

* `auto_generate_proxies` configuration values are `Doctrine\Common\Proxy\AbstractProxyFactory` constants, in both cases it defaults to `Doctrine\Common\Proxy\AbstractProxyFactory::AUTOGENERATE_NEVER` (0).

* Managers are being configured **ready for production** and not for development, this mainly means proxies won't be automatically generated and, in case no `cache_driver` is provided, one will be auto-generated in the following order depending on availability: `ApcCache`, `XcacheCache`, `MemcacheCache`, `RedisCache` or finally fall back to `ArrayCache` which is always available. It is recommended you provide your cache always, for development you can use `VoidCache`.

## CLI tool

Find here an example of `cli-config.php` file that can be used as a template:

```php
require __DIR__ . '/vendor/autoload.php';

use Doctrine\ODM\MongoDB\Tools\Console\Helper\DocumentManagerHelper;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Jgut\Slim\Doctrine\DocumentManagerBuilder;
use Jgut\Slim\Doctrine\EntityManagerBuilder;

$CLISettings = [
    'cache_driver' => new VoidCache,
];
$settings = require 'configurations.php';

$entityManager = EntityManagerBuilder::build(array_merge($settings['entity_manager'], $CLISettings));
$documentManager = DocumentManagerBuilder::build(array_merge($settings['document_manager'], $CLISettings));

$helperSet = ConsoleRunner::createHelperSet($entityManager);
$helperSet->set(new DocumentManagerHelper($documentManager), 'dm');

$cli = ConsoleRunner::createApplication($helperSet, [
    new \Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateDocumentsCommand(),
    new \Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateHydratorsCommand(),
    new \Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateProxiesCommand(),
    new \Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateRepositoriesCommand(),
    new \Doctrine\ODM\MongoDB\Tools\Console\Command\QueryCommand(),
    new \Doctrine\ODM\MongoDB\Tools\Console\Command\ClearCache\MetadataCommand(),
    new \Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\CreateCommand(),
    new \Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\DropCommand(),
    new \Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\UpdateCommand(),
]);

return $cli->run();
```

## Contributing

Found a bug or have a feature request? [Please open a new issue](https://github.com/juliangut/slim-doctrine/issues). Have a look at existing issues before

See file [CONTRIBUTING.md](https://github.com/juliangut/slim-doctrine/blob/master/CONTRIBUTING.md)

### Contributors

* [@fousheezy (John Foushee)](https://github.com/fousheezy)
* [@mcrauwel (Matthias Crauwels)](https://github.com/mcrauwel)
* [@mgersten (Micah Gersten)](https://github.com/mgersten)

## License

See file [LICENSE](https://github.com/juliangut/slim-doctrine/blob/master/LICENSE) included with the source code for a copy of the license terms

