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
use Psr\Container\ContainerInterface;

// Implementation of ContainerInterface
$container = '';

$options = [
    'relationalManagerKey' => 'orm',
    'defaultRelationalManagerName' => 'entity_manager',
    'mongoDBManagerKey' => 'odm',
    'defaultMongoManagerName' => 'document_manager',
    'container' => $container,
];

$managerBuilder = new ManagerBuilder($options);

$managerBuilder->setRelationalManagerKey('orm');
$managerBuilder->setDefaultRelationalManagerName('entity_manager');
$managerBuilder->setMongoDBManagerKey('odm');
$managerBuilder->setDefaultRelationalManagerName('document_manager');
$managerBuilder->setContainer($container);
```

When possible include in implementation of ContainerInterface as it'll be used for repository creation. See below

### Manager builders

In order to configure the different Doctrine manager builders head to [juliangut/doctrine-manager-builder](https://github.com/juliangut/doctrine-manager-builder) to review manager configurations.

## Usage

Register managers in the DI container as any other service.

```php
use Jgut\Slim\Doctrine\ManagerBuilder;
use Psr\Container\ContainerInterface;
use Slim\App;

// Implementation of ContainerInterface
$container = '';

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
    'container' =>> $container,
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

### Manager defaults

Managers are automatically configured with custom repository factories (`Jgut\Slim\Doctrine\Repository\RelationalRepositoryFactory` and `Jgut\Slim\Doctrine\Repository\MongoDbRepositoryFactory`) which improves repository creation with the help of [PHP-DI/Invoker](https://github.com/PHP-DI/Invoker)

Default repository will be `Jgut\Slim\Doctrine\Repository\RelationalRepository` or `Jgut\Slim\Doctrine\Repository\MongoDbRepository` which comes with several new methods, you can extend from them to use them

#### Counting

Perform object count with countAll and countBy with "magic" combinations as well

```php
$repository = $manager->getRepository(ObjectClass::class);

$totalObjects = $repository->countAll();
$activeObjects = $repository->countBy(['active' => true]);
$activeObjects = $repository->countByActive(true);
```

#### Find or fail

Same functionality as findBy, findOneBy and their "magic" combinations but throwing an exception if nothing is found

```php
$repository = $manager->getRepository(ObjectClass::class);

$object = $repository->findByOrFail(['slug' => 'my_slug']);
$object = $repository->findBySlugOrFail('my_slug');

$object = $repository->findOneByOrFail(['slug' => 'my_slug']);
$object = $repository->findOneBySlugOrFail('my_slug');
```

#### Creation

##### getNew

Creates a new empty object directly from repository.

```php
$repository = $manager->getRepository(ObjectClass::class);

$newObject = $repository->getNew();
```

If your object needs constructor parameters override getNew method on your own repositories

##### findOneByOrGetNew

Returns an object based on criteria or a new object if none could not be found.

```php
$repository = $manager->getRepository(ObjectClass::class);

$existingOrNewObject = $repository->findOneByOrGetNew(['slug' => 'my_slug']);
$existingOrNewObject = $repository->findOneBySlugOrGetNew('my_slug');
```

#### Persisting

Will persist the object into the manager.

```php
$repository = $manager->getRepository(ObjectClass::class);

$repository->persist(new ObjectClass());
```

#### Removal

##### remove

In the same fashion as add this will remove the object.

```php
$repository = $manager->getRepository(ObjectClass::class);

$repository->remove($repository->findById(1));
```

##### removeAll

FindAll and then removes them all.

```php
$repository = $manager->getRepository(ObjectClass::class);

$repository->removeAll();
```

##### removeBy and removeOneBy

As their counterparts findBy and findOneBy but removing the objects instead of returning them

```php
$repository = $manager->getRepository(ObjectClass::class);

$repository->removeBy(['active' => false]);
$repository->removeByActive(false);

$repository->removeOneBy(['active' => true]);
$repository->removeOneByActive(true);
```

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

