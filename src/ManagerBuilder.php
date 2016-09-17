<?php
/**
 * Slim3 Doctrine integration (https://github.com/juliangut/slim-doctrine)
 *
 * @link https://github.com/juliangut/slim-doctrine for the canonical source repository
 *
 * @license https://raw.githubusercontent.com/juliangut/slim-doctrine/master/LICENSE
 */

namespace Jgut\Slim\Doctrine;

use Interop\Container\ContainerInterface;
use Jgut\Doctrine\ManagerBuilder\CouchDBBuilder;
use Jgut\Doctrine\ManagerBuilder\ManagerBuilder as Builder;
use Jgut\Doctrine\ManagerBuilder\MongoDBBuilder;
use Jgut\Doctrine\ManagerBuilder\RelationalBuilder;
use Symfony\Component\Console\Application;

/**
 * Slim-Doctrine managers integration.
 */
class ManagerBuilder
{
    /**
     * @var Builder[]
     */
    protected $builders = [];

    /**
     * Load Doctrine managers from container configuration.
     *
     * @param ContainerInterface $container
     * @param string             $key
     *
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \Interop\Container\Exception\NotFoundException
     * @throws \RuntimeException
     *
     * @return $this
     */
    public function loadFromContainer(ContainerInterface $container, $key)
    {
        return $this->loadFromArray((array) $container->get((string) $key));
    }

    /**
     * Load Doctrine managers from configuration array.
     *
     * @param array $settings
     *
     * @throws \RuntimeException
     *
     * @return $this
     */
    public function loadFromArray(array $settings)
    {
        if (array_key_exists('entity_manager', $settings)) {
            $this->registerEntityManagers((array) $settings['entity_manager']);
        }

        if (array_key_exists('mongodb_document_manager', $settings)) {
            $this->registerMongoDBDocumentManagers((array) $settings['mongodb_document_manager']);
        }

        if (array_key_exists('couchdb_document_manager', $settings)) {
            $this->registerCouchDBDocumentManagers((array) $settings['couchdb_document_manager']);
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
            $settings = (array) $settings;
        }

        foreach ($settings as $name => $config) {
            if (!is_string($name)) {
                $name = 'entityManager';
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
            $settings = (array) $settings;
        }

        foreach ($settings as $name => $config) {
            if (!is_string($name)) {
                $name = 'mongoDocumentManager';
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
            $settings = (array) $settings;
        }

        foreach ($settings as $name => $config) {
            if (!is_string($name)) {
                $name = 'couchDocumentManager';
            }

            $this->addBuilder(new CouchDBBuilder($config, $name));
        }
    }

    /**
     * Add builder.
     *
     * @param Builder $builder
     *
     * @throws \RuntimeException
     *
     * @return $this
     */
    protected function addBuilder(Builder $builder)
    {
        $builderName = $builder->getName();

        if (array_key_exists($builderName, $this->builders)) {
            throw new \RuntimeException(sprintf('"%s" manager builder is already registered', $builderName));
        }

        $this->builders[$builder->getName()] = $builder;

        return $this;
    }

    /**
     * Get registered builders.
     *
     * @return \Doctrine\Common\Persistence\ObjectManager[]
     */
    public function getManagers()
    {
        return array_map(
            function (Builder $builder) {
                return $builder->getManager();
            },
            $this->builders
        );
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
        if (!array_key_exists($name, $this->builders)) {
            throw new \RuntimeException(sprintf('"%s" is not a registered manager', $name));
        }

        return $this->builders[$name]->getManager();
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
            $helperSet = $builder->getConsoleHelperSet();

            foreach ($builder->getConsoleCommands() as $command) {
                $application->add($command)->setHelperSet($helperSet);
            }
        }

        return $application;
    }
}
