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
use Jgut\Doctrine\ManagerBuilder\AbstractBuilderCollection;
use Jgut\Doctrine\ManagerBuilder\CouchDBBuilder;
use Jgut\Doctrine\ManagerBuilder\ManagerBuilder as Builder;
use Jgut\Doctrine\ManagerBuilder\MongoDBBuilder;
use Jgut\Doctrine\ManagerBuilder\RelationalBuilder;
use Symfony\Component\Console\Application;

/**
 * Slim-Doctrine managers integration.
 */
class ManagerBuilder extends AbstractBuilderCollection
{
    const METADATA_MAPPING_ANNOTATION = Builder::METADATA_MAPPING_ANNOTATION;
    const METADATA_MAPPING_XML = Builder::METADATA_MAPPING_XML;
    const METADATA_MAPPING_YAML = Builder::METADATA_MAPPING_YAML;
    const METADATA_MAPPING_PHP = Builder::METADATA_MAPPING_PHP;

    /**
     * Global annotation loader control.
     *
     * @var bool
     */
    protected $globalLoaderRegister = true;

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
            $settings = [$settings];
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
            $settings = [$settings];
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
            $settings = [$settings];
        }

        foreach ($settings as $name => $config) {
            if (!is_string($name)) {
                $name = 'couchDocumentManager';
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
            $helperSet = $builder->getConsoleHelperSet();

            foreach ($builder->getConsoleCommands() as $command) {
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
