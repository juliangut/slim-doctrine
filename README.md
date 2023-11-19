[![PHP version](https://img.shields.io/badge/PHP-%3E%3D8.0-8892BF.svg?style=flat-square)](http://php.net)
[![Latest Version](https://img.shields.io/packagist/v/juliangut/slim-doctrine.svg?style=flat-square)](https://packagist.org/packages/juliangut/slim-doctrine)
[![License](https://img.shields.io/github/license/juliangut/slim-doctrine.svg?style=flat-square)](https://github.com/juliangut/slim-doctrine/blob/master/LICENSE)

[![Total Downloads](https://img.shields.io/packagist/dt/juliangut/slim-doctrine.svg?style=flat-square)](https://packagist.org/packages/juliangut/slim-doctrine)
[![Monthly Downloads](https://img.shields.io/packagist/dm/juliangut/slim-doctrine.svg?style=flat-square)](https://packagist.org/packages/juliangut/slim-doctrine)

# Slim integration with Doctrine managers

Easy Slim framework integration with Doctrine's ORM Entity Manager and MongoDB Document Manager.

## Installation

Best way to install is using [Composer](https://getcomposer.org/):

```
composer require juliangut/slim-doctrine
```

### Using ORM

```
composer require doctrime/orm:^2.13
```

#### With Doctrine Migrations

```
composer require doctrime/migrations:^3.5
```

### Using MongoDB ODM

```
composer require doctrine/mongodb-odm:^2.3
```

Then require_once the autoload file:

```php
require_once './vendor/autoload.php';
```

## Configuration

Each kind of manager should have its configurations stored on a key in the array of settings
 
* "entityManager" for relational managers
* "documentManager" for MongoDB managers

```php
[
    'entityManager' => [
        'manager1_name' => <relational_manager_builder_configuration>,
        'manager2_name' => <relational_manager_builder_configuration>,
        ...
    ],
    'documentManager' => [
        'manager3_name' => <mongodb_manager_builder_configuration>,
        'manager4_name' => <mongodb_manager_builder_configuration>,
        ...
    ],
]
```

If a manager is not given a name then a default one will be used:

* "entityManager" for relational managers
* "documentManager" for MongoDB managers

### Options

ManagerBuilder's default keys and names can be modified at constructor with an options array or calling the corresponding method

```php
$options = [
    'relationalManagerKey' => 'orm',
    'defaultRelationalManagerName' => 'entity_manager',
    'mongoDBManagerKey' => 'odm',
    'defaultMongoManagerName' => 'document_manager',
];

$managerBuilder = new ManagerBuilder($options);

$managerBuilder->setRelationalManagerKey('orm');
$managerBuilder->setDefaultRelationalManagerName('entity_manager');
$managerBuilder->setMongoDBManagerKey('odm');
$managerBuilder->setDefaultRelationalManagerName('document_manager');
```

### Manager builders

In order to configure the different Doctrine manager builders head to [juliangut/doctrine-manager-builder](https://github.com/juliangut/doctrine-manager-builder) to review manager configurations.

## Usage

Register managers in the DI container as any other service.

```php
use Jgut\Slim\Doctrine\ManagerBuilder;
use Slim\App;

// Probably loaded from a file
$managerSettings = [
    'orm' => [
        'mainManager' => [
            'connection' => [
                'driver' => 'pdo_mysql',
                'user' => 'db_user',
                'password' => 'db_password',
                'host' => 'localhost',
                'dbname' => 'db_name',
            ],
            'metadataMapping' => [
                [
                    'type' => ManagerBuilder::METADATA_MAPPING_ATTRIBUTE,
                    'path' => 'path/to/relational/mappings',
                ],
            ],
            'migrationsConfiguration' => [
                'migrations_paths' => [
                    'App\Migrations' => __DIR__ . '/files/migrations',
                ],
            ],
        ],
    ],
    'odm' => [
        'auxManager' => [
            'client' => [
                'uri' => 'mongodb://localhost:27017'
            ],
            'metadataMapping' => [
                [
                    'type' => ManagerBuilder::METADATA_MAPPING_ATTRIBUTE,
                    'path' => 'path/to/mongodb/mappings',
                ],
            ],
        ],
    ],
];

$managerBuilder = new ManagerBuilder([
    'relationalManagerKey' => 'orm',
    'mongoDBManagerKey' => 'odm',
]);

// Register all managers at once
$managerBuilder->registerManagers($managerSettings);

// Or register independently
$managerBuilder->registerRelationalManagers($managerSettings['orm']);
$managerBuilder->registerMongoDbDocumentManagers($managerSettings['odm']);

// Create Slim app and fetch DI Container
$app = new App();

// Register managers into the container
$container = $app->getContainer();
foreach ($managerBuilder->getManagers() as $name => $manager) {
    $container[$name] = $manager;
}

// Use managers
$app->get('/', function () {
    $this->mainManager->persist(new \Entity);
    $this->mainManager->flush();

    $this->auxManager->persist(new \Document);
    $this->auxManager->flush();
});
```

If you are using another kind of container such as PHP-DI it'd be a good idea to register managers as independent resolvable services

## CLI Application builder

`doctrine-manager` is a CLI tool that is installed with this package. It provides the same functionality that Doctrine's ORM `doctrine` CLI tool does, but it does not need ORM to be installed. Additionally `doctrine-manager` allows you to have numerous managers configured thanks to prepending manager names.

The way to using `doctrine-manager` is the same as with `doctrine` by creating a `cli-config.php` file returning a Symfony\Component\Console\Application

```php
require __DIR__ . '/vendor/autoload.php';

use Jgut\Slim\Doctrine\ManagerBuilder;

$settings = require 'configurations.php';

$managerBuilder = (new ManagerBuilder())->registerManagers($settings);

return $managerBuilder->getCLIApplication();
```

## Migrating from 2.x

* Minimum PHP version is now 8.0
* Minimum doctrine/orm is now 2.13
* Minimum doctrine/mongodb-odm is now 2.3
* Configuration names have changed to camelCase
* `loadSettings` method has been renamed to `registerManagers`
* Annotation mapping is deprecated, migrate to Attribute mapping

## Contributing

Found a bug or have a feature request? [Please open a new issue](https://github.com/juliangut/slim-doctrine/issues). Have a look at existing issues before

See file [CONTRIBUTING.md](https://github.com/juliangut/slim-doctrine/blob/master/CONTRIBUTING.md)

### Contributors

* [@mcrauwel (Matthias Crauwels)](https://github.com/mcrauwel)

## License

See file [LICENSE](https://github.com/juliangut/slim-doctrine/blob/master/LICENSE) included with the source code for a copy of the license terms

