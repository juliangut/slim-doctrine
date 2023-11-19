<?php

/*
 * (c) 2016-2023 Julián Gutiérrez <juliangut@gmail.com>
 *
 * @license BSD-3-Clause
 * @link https://github.com/juliangut/slim-doctrine
 */

declare(strict_types=1);

namespace Jgut\Slim\Doctrine;

use Doctrine\Persistence\ObjectManager;
use InvalidArgumentException;
use Jgut\Doctrine\ManagerBuilder\AbstractBuilderCollection;
use Jgut\Doctrine\ManagerBuilder\ManagerBuilder as Builder;
use Jgut\Doctrine\ManagerBuilder\MongoDBBuilder;
use Jgut\Doctrine\ManagerBuilder\RelationalBuilder;
use RuntimeException;
use Symfony\Component\Console\Application;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class ManagerBuilder extends AbstractBuilderCollection
{
    public const METADATA_MAPPING_ATTRIBUTE = Builder::METADATA_MAPPING_ATTRIBUTE;
    public const METADATA_MAPPING_XML = Builder::METADATA_MAPPING_XML;
    public const METADATA_MAPPING_PHP = Builder::METADATA_MAPPING_PHP;
    public const METADATA_MAPPING_YAML = Builder::METADATA_MAPPING_YAML;
    public const METADATA_MAPPING_ANNOTATION = Builder::METADATA_MAPPING_ANNOTATION;

    private string $relationalManagerKey = 'entity_manager';

    private string $defaultRelationalManagerName = 'entityManager';

    private string $mongoDbManagerKey = 'document_manager';

    private string $defaultMongoDbManagerName = 'documentManager';

    /**
     * @param array<string, mixed> $options
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $options = [])
    {
        foreach ($options as $option => $value) {
            $method = 'set' . ucfirst($option);
            if (!method_exists($this, $method)) {
                throw new InvalidArgumentException(sprintf('Unknown configuration "%s".', $option));
            }

            /** @var callable(mixed): void $callable */
            $callable = [$this, $method];

            $callable($value);
        }
    }

    public function setRelationalManagerKey(string $relationalManagerKey): void
    {
        $this->relationalManagerKey = $relationalManagerKey;
    }

    public function setDefaultRelationalManagerName(string $defaultRelationalManagerName): void
    {
        $this->defaultRelationalManagerName = $defaultRelationalManagerName;
    }

    public function setMongoDbManagerKey(string $mongoDbManagerKey): void
    {
        $this->mongoDbManagerKey = $mongoDbManagerKey;
    }

    public function setDefaultMongoDbManagerName(string $defaultMongoDbManagerName): void
    {
        $this->defaultMongoDbManagerName = $defaultMongoDbManagerName;
    }

    /**
     * @param array<string, array<int|string, array<string, mixed>>|array<string, mixed>> $settings
     *
     * @throws InvalidArgumentException
     */
    public function registerManagers(array $settings): void
    {
        if (\array_key_exists($this->relationalManagerKey, $settings)) {
            $this->registerRelationalManagers($settings[$this->relationalManagerKey]);
        }

        if (\array_key_exists($this->mongoDbManagerKey, $settings)) {
            $this->registerMongoDbDocumentManagers($settings[$this->mongoDbManagerKey]);
        }
    }

    /**
     * @param array<int|string, array<string, mixed>>|array<string, mixed> $managersSettings
     *
     * @throws InvalidArgumentException
     */
    public function registerRelationalManagers(array $managersSettings): void
    {
        if (\array_key_exists('connection', $managersSettings)) {
            $managersSettings = [$managersSettings];
        }

        /** @var array<int|string, array<string, mixed>> $managersSettings */
        foreach ($managersSettings as $name => $managerSettings) {
            if (\is_string($name) && $name !== '') {
                $managerSettings['name'] = $name;
            }

            $this->registerRelationalManager($managerSettings);
        }
    }

    /**
     * @param array<string, mixed> $managerSettings
     */
    public function registerRelationalManager(array $managerSettings): void
    {
        $managerSettings['name'] ??= $this->defaultRelationalManagerName;

        $this->addBuilder(new RelationalBuilder($managerSettings));
    }

    /**
     * @param array<int|string, array<string, mixed>>|array<string, mixed> $managersSettings
     *
     * @throws InvalidArgumentException
     */
    public function registerMongoDbDocumentManagers(array $managersSettings): void
    {
        if (\array_key_exists('client', $managersSettings)) {
            $managersSettings = [$managersSettings];
        }

        /** @var array<int|string, array<string, mixed>> $managersSettings */
        foreach ($managersSettings as $name => $managerSettings) {
            if (\is_string($name) && $name !== '') {
                $managerSettings['name'] = $name;
            }

            $this->registerMongoDbDocumentManager($managerSettings);
        }
    }

    /**
     * @param array<string, mixed> $managerSettings
     */
    public function registerMongoDbDocumentManager(array $managerSettings): void
    {
        $managerSettings['name'] ??= $this->defaultMongoDbManagerName;

        $this->addBuilder(new MongoDBBuilder($managerSettings));
    }

    /**
     * @return array<string, ObjectManager>
     */
    public function getManagers(): array
    {
        return array_map(static fn(Builder $builder): ObjectManager => $builder->getManager(), $this->builders);
    }

    /**
     * @throws RuntimeException
     */
    public function getManager(string $name): ObjectManager
    {
        $builder = $this->getBuilder($name);
        if ($builder === null) {
            throw new RuntimeException(sprintf('"%s" is not a registered manager.', $name));
        }

        return $builder->getManager();
    }

    public function getCliApplication(?string $commandPrefix = null): Application
    {
        $application = new Application('Doctrine Manager Builder Command Line Interface');
        $application->setAutoExit(true);
        $application->setCatchExceptions(true);

        $this->addCliApplicationCommands($application, $commandPrefix);

        return $application;
    }

    public function addCliApplicationCommands(Application $application, ?string $commandPrefix = null): void
    {
        foreach ($this->builders as $builder) {
            foreach ($builder->getConsoleCommands() as $command) {
                if ($commandPrefix !== null) {
                    $command->setName(sprintf('%s:%s', rtrim($commandPrefix, ':'), $command->getName()));
                }

                $application->add($command);
            }
        }
    }
}
