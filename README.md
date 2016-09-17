[![PHP version](https://img.shields.io/badge/PHP-%3E%3D5.5-8892BF.svg?style=flat-square)](http://php.net)
[![Latest Version](https://img.shields.io/packagist/vpre/juliangut/slim-doctrine.svg?style=flat-square)](https://packagist.org/packages/juliangut/slim-doctrine)
[![License](https://img.shields.io/github/license/juliangut/slim-doctrine.svg?style=flat-square)](https://github.com/juliangut/slim-doctrine/blob/master/LICENSE)

[![Build status](https://img.shields.io/travis/juliangut/slim-doctrine.svg?style=flat-square)](https://travis-ci.org/juliangut/slim-doctrine)
[![Style](https://styleci.io/repos/42014429/shield)](https://styleci.io/repos/42014429)
[![Code Quality](https://img.shields.io/scrutinizer/g/juliangut/slim-doctrine.svg?style=flat-square)](https://scrutinizer-ci.com/g/juliangut/slim-doctrine)
[![Code Coverage](https://img.shields.io/coveralls/juliangut/slim-doctrine.svg?style=flat-square)](https://coveralls.io/github/juliangut/slim-doctrine)
[![Total Downloads](https://img.shields.io/packagist/dt/juliangut/slim-doctrine.svg?style=flat-square)](https://packagist.org/packages/juliangut/slim-doctrine)

# Slim Doctrine managers integration

Frees you from the tedious work of configuring and integrating Doctrine's Entity Manager, MongoDB Document Manager and CouchDB Document Manager.

## Important note

This latest version of slim-doctrine is focused on Slim framework and Doctrine integration and thus using the manager builders as stand alone is not possible.

For that head to [juliangut/doctrine-manager-builder](https://github.com/juliangut/doctrine-manager-builder) which provides that possibility without tying you to Slim framework.

## Installation

Best way to install is using [Composer](https://getcomposer.org/):

```
composer require juliangut/slim-doctrine
```

Then require_once the autoload file:

```php
require_once './vendor/autoload.php';
```

## Configuration

Each kind of manager has its configurations stored on a key in the settings array
 
* `entity_manager` for `ORM`
* `mongodb_document_manager` for `MongoDB ODM`
* `couchdb_document_manager` for `CouchDB ODM`

```php
[
    'entity_manager' => [
        'manager_name' => <manager_builder_configuration>,
        'manager_name' => <manager_builder_configuration>,
        ...
    ],
    'mongodb_document_manager' => [
        'manager_name' => <manager_builder_configuration>,
        'manager_name' => <manager_builder_configuration>,
        ...
    ],
    'couchdb_document_manager' => [
        'manager_name' => <manager_builder_configuration>,
        'manager_name' => <manager_builder_configuration>,
        ...
    ],
]
```

If no name provided for a manager a default one will be given:

* `entityManager` for `entity_manager`
* `mongoDocumentManager` for `mongodb_document_manager`
* `couchDocumentManager` for `couchdb_document_manager`

### Manager builders

In order to configure the different Doctrine manager builders head to [juliangut/doctrine-manager-builder](https://github.com/juliangut/doctrine-manager-builder) which is used in this package.

## Usage

Register managers in the DI container as any other service.

```php
use Jgut\Slim\Doctrine\ManagerBuilder;
use Slim\App;

// Loaded from a file
$settings = [
    'entity_manager' => [
        'connection' => [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ],
        'annotation_paths' => ['path_to_entities_files'],
    ],
];

// Create manager builder loading settings array
$managerBuilder = (new ManagerBuilder())->loadFromArray($settings);

// Create Slim app and fetch DI Container
$app = new App();
$container = $app->getContainer();

// Register every manager in the container
foreach ($managerBuilder->getManagers as $name => $manager) {
    $container[$name] = $manager;
}

// Use managers
$app->get('/', function () {
    $this->entityManager->persist(new \Entity);
    $this->entityManager->flush();
});
```

Register manager builder in the DI container to delegate managers creation.

```php
use Jgut\Slim\Doctrine\ManagerBuilder;
use Interop\Container\ContainerInterface;
use Slim\App;

// Loaded from a file
$settings = [
    'doctrine_managers' => [
        'mongodb_document_manager' => [
            'mainDocumentManager' => [
                'connection' => [
                    'server' => 'mongodb://localhost:27017',
                ],
                'annotation_paths' => ['path_to_documents_files'],
            ],
            'secondaryDocumentManager' => [
                'connection' => [
                    'server' => 'mongodb://localhost:27017',
                ],
                'annotation_paths' => ['path_to_documents_files'],
            ],
        ],
    ],
];

// Create Slim app and fetch DI Container
$app = new App($settings);
$container = $app->getContainer();

// Register manager builder fetching settings from container
$container['manager_builder'] => function (ContainerInterface $container) {
    return (new ManagerBuilder())->loadFromContainer($container, 'doctrine_managers');
};

// Register managers by pulling them from the builder
$container['mainDocumentManager'] => function (ContainerInterface $container) {
    return $container->get('manager_builder')->getManager('mainDocumentManager');
};

// Use managers
$app->get('/', function () {
    $this->mainDocumentManager->persist(new \Document);
    $this->mainDocumentManager->flush();
});
```

When using settings in the container with `loadFromContainer` method you must specify under which key they are stored. In the example above `doctrine_managers` is used.

## CLI Application builder

`doctrine-manager` is a CLI tool that is installed with this package. It provides the same functionality that Doctrine's ORM `doctrine` CLI tool does but it doesn't need ORM to be installed. Additionally `doctrine-manager` allows you to have numerous managers configured thanks to prepending manager name.

The way to using `doctrine-manager` is the same as with `doctrine` by creating a `cli-config.php` file

```php
require __DIR__ . '/vendor/autoload.php';

use Jgut\Slim\Doctrine\ManagerBuilder;

$settings = require 'configurations.php';

$managerBuilder = (new ManagerBuilder())->loadFromArray($settings);

return $managerBuilder->getCLIApplication();
```

## Contributing

Found a bug or have a feature request? [Please open a new issue](https://github.com/juliangut/slim-doctrine/issues). Have a look at existing issues before

See file [CONTRIBUTING.md](https://github.com/juliangut/slim-doctrine/blob/master/CONTRIBUTING.md)

### Contributors

* [@mcrauwel (Matthias Crauwels)](https://github.com/mcrauwel)

## License

See file [LICENSE](https://github.com/juliangut/slim-doctrine/blob/master/LICENSE) included with the source code for a copy of the license terms

