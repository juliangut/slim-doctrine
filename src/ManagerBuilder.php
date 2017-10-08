<?php

/*
 * Slim3 Doctrine integration (https://github.com/juliangut/slim-doctrine).
 *
 * @license BSD-3-Clause
 * @link https://github.com/juliangut/slim-doctrine
 * @author Julián Gutiérrez <juliangut@gmail.com>
 */

namespace Jgut\Slim\Doctrine;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Jgut\Doctrine\ManagerBuilder\AbstractBuilderCollection;
use Jgut\Doctrine\ManagerBuilder\CouchDBBuilder;
use Jgut\Doctrine\ManagerBuilder\ManagerBuilder as Builder;
use Jgut\Doctrine\ManagerBuilder\MongoDBBuilder;
use Jgut\Doctrine\ManagerBuilder\RelationalBuilder;
use Jgut\Doctrine\ManagerBuilder\Util\OptionsTrait;
use Symfony\Component\Console\Application;

/**
 * Slim-Doctrine managers integration.
 */
class ManagerBuilder extends AbstractBuilderCollection
{
    use OptionsTrait;

    const METADATA_MAPPING_ANNOTATION = Builder::METADATA_MAPPING_ANNOTATION;
    const METADATA_MAPPING_XML = Builder::METADATA_MAPPING_XML;
    const METADATA_MAPPING_YAML = Builder::METADATA_MAPPING_YAML;
    const METADATA_MAPPING_PHP = Builder::METADATA_MAPPING_PHP;

    const RELATIONAL_MANAGER_KEY = 'relational_manager_key';
    const MONGODB_MANAGER_KEY = 'mongodb_manager_key';
    const COUCHDB_MANAGER_KEY = 'couchdb_manager_key';

    const DEFAULT_RELATIONAL_MANAGER_KEY = 'entity_manager';
    const DEFAULT_MONGODB_MANAGER_KEY = 'mongodb_document_manager';
    const DEFAULT_COUCHDB_MANAGER_KEY = 'couchdb_document_manager';

    const RELATIONAL_MANAGER_NAME = 'relational_manager_name';
    const MONGODB_MANAGER_NAME = 'mongodb_manager_name';
    const COUCHDB_MANAGER_NAME = 'couchdb_manager_name';

    const DEFAULT_RELATIONAL_MANAGER_NAME = 'entityManager';
    const DEFAULT_MONGODB_MANAGER_NAME = 'mongoDocumentManager';
    const DEFAULT_COUCHDB_MANAGER_NAME = 'couchDocumentManager';

    /**
     * Global annotation loader control.
     *
     * @var bool
     */
    protected $globalLoaderRegister = true;

    /**
     * ManagerBuilder constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $options = array_merge(
            [
                static::RELATIONAL_MANAGER_KEY => static::DEFAULT_RELATIONAL_MANAGER_KEY,
                static::MONGODB_MANAGER_KEY => static::DEFAULT_MONGODB_MANAGER_KEY,
                static::COUCHDB_MANAGER_KEY => static::DEFAULT_COUCHDB_MANAGER_KEY,
                static::RELATIONAL_MANAGER_NAME => static::DEFAULT_RELATIONAL_MANAGER_NAME,
                static::MONGODB_MANAGER_NAME => static::DEFAULT_MONGODB_MANAGER_NAME,
                static::COUCHDB_MANAGER_NAME => static::DEFAULT_COUCHDB_MANAGER_NAME,
            ],
            $options
        );

        $this->setOptions($options);
    }

    /**
     * Load Doctrine managers from settings array.
     *
     * @param array $settings
     *
     * @throws \RuntimeException
     *
     * @return $this
     */
    public function loadSettings(array $settings)
    {
        $relationalManagerKey = $this->getOption(static::RELATIONAL_MANAGER_KEY);
        if (array_key_exists($relationalManagerKey, $settings)) {
            $this->registerEntityManagers((array) $settings[$relationalManagerKey]);
        }

        $mongoDBManagerKey = $this->getOption(static::MONGODB_MANAGER_KEY);
        if (array_key_exists($mongoDBManagerKey, $settings)) {
            $this->registerMongoDBDocumentManagers((array) $settings[$mongoDBManagerKey]);
        }

        $couchDBManagerKey = $this->getOption(static::COUCHDB_MANAGER_KEY);
        if (array_key_exists($couchDBManagerKey, $settings)) {
            $this->registerCouchDBDocumentManagers((array) $settings[$couchDBManagerKey]);
        }

        return $this;
    }

    /**
     * Register ORM entity managers.
     *
     * @param array $settings
     *
     * @throws \RuntimeException
     */
    protected function registerEntityManagers(array $settings)
    {
        if (array_key_exists('connection', $settings)) {
            $settings = [$settings];
        }

        foreach ($settings as $name => $config) {
            if (!is_string($name)) {
                $name = $this->getOption(static::RELATIONAL_MANAGER_NAME);
            }

            $this->addBuilder(new RelationalBuilder($config, $name));
        }
    }

    /**
     * Register MongoDB ODM document managers.
     *
     * @param array $settings
     *
     * @throws \RuntimeException
     */
    protected function registerMongoDBDocumentManagers(array $settings)
    {
        if (array_key_exists('connection', $settings)) {
            $settings = [$settings];
        }

        foreach ($settings as $name => $config) {
            if (!is_string($name)) {
                $name = $this->getOption(static::MONGODB_MANAGER_NAME);
            }

            $this->addBuilder(new MongoDBBuilder($config, $name));
        }
    }

    /**
     * Register CouchDB ODM document managers.
     *
     * @param array $settings
     *
     * @throws \RuntimeException
     */
    protected function registerCouchDBDocumentManagers(array $settings)
    {
        if (array_key_exists('connection', $settings)) {
            $settings = [$settings];
        }

        foreach ($settings as $name => $config) {
            if (!is_string($name)) {
                $name = $this->getOption(static::COUCHDB_MANAGER_NAME);
            }

            $this->addBuilder(new CouchDBBuilder($config, $name));
        }
    }

    /**
     * Get registered builder's managers.
     *
     * @return \Doctrine\Common\Persistence\ObjectManager[]
     */
    public function getManagers()
    {
        $managers = array_map(
            function (Builder $builder) {
                return $builder->getManager();
            },
            $this->builders
        );

        $this->registerGlobalAnnotationLoader();

        return $managers;
    }

    /**
     * Get registered builder's manager.
     *
     * @param string $name
     *
     * @throws \RuntimeException
     *
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    public function getManager($name)
    {
        $builder = $this->getBuilder($name);
        if (!$builder instanceof Builder) {
            throw new \RuntimeException(sprintf('"%s" is not a registered manager', $name));
        }

        $manager = $builder->getManager();

        $this->registerGlobalAnnotationLoader();

        return $manager;
    }

    /**
     * Get console application.
     *
     * @return Application
     */
    public function getCLIApplication()
    {
        $application = new Application('Doctrine Manager Builder Command Line Interface');
        $application->setCatchExceptions(true);

        foreach ($this->builders as $builder) {
            foreach ($builder->getConsoleCommands() as $command) {
                $helperSet = $command->getHelperSet();

                $application->add($command)->setHelperSet($helperSet);
            }
        }

        $this->registerGlobalAnnotationLoader();

        return $application;
    }

    /**
     * Register global annotation loader.
     * class_exists function.
     */
    protected function registerGlobalAnnotationLoader()
    {
        if ($this->globalLoaderRegister) {
            AnnotationRegistry::registerLoader('class_exists');

            $this->globalLoaderRegister = false;
        }
    }
}
