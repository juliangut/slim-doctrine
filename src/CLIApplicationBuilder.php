<?php
/**
 * Slim3 Doctrine integration (https://github.com/juliangut/slim-doctrine)
 *
 * @link https://github.com/juliangut/slim-doctrine for the canonical source repository
 *
 * @license https://raw.githubusercontent.com/juliangut/slim-doctrine/master/LICENSE
 */

namespace Jgut\Slim\Doctrine;

use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Tools\Console\Command as ODMCommand;
use Doctrine\ODM\MongoDB\Tools\Console\Helper\DocumentManagerHelper;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * Doctrine CLI application builder.
 */
class CLIApplicationBuilder
{
    /**
     * Create a Doctrine CLI application.
     *
     * @param array|EntityManager|null   $entityManager
     * @param array|DocumentManager|null $documentManager
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\ORMException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \Symfony\Component\Console\Exception\LogicException
     *
     * @return \Symfony\Component\Console\Application
     */
    public static function build($entityManager = null, $documentManager = null)
    {
        if ($entityManager === null && $documentManager === null) {
            throw new \InvalidArgumentException('At least one of EntityManager or DocumentManager must be provided');
        }

        if ($entityManager !== null) {
            $entityManager = static::getEntityManager($entityManager);
        }

        if ($documentManager !== null) {
            $documentManager = static::getDocumentManager($documentManager);
        }

        $helperSet = new HelperSet;

        if ($entityManager instanceof EntityManager) {
            $helperSet->set(new ConnectionHelper($entityManager->getConnection()), 'db');
            $helperSet->set(new EntityManagerHelper($entityManager), 'em');
        }

        if ($documentManager instanceof DocumentManager) {
            $helperSet->set(new DocumentManagerHelper($documentManager), 'dm');
        }

        $application = ConsoleRunner::createApplication($helperSet);

        if ($documentManager instanceof DocumentManager) {
            $application->addCommands(
                [
                    new ODMCommand\GenerateDocumentsCommand,
                    new ODMCommand\GenerateHydratorsCommand,
                    new ODMCommand\GenerateProxiesCommand,
                    new ODMCommand\GenerateRepositoriesCommand,
                    new ODMCommand\QueryCommand,
                    new ODMCommand\ClearCache\MetadataCommand,
                    new ODMCommand\Schema\CreateCommand,
                    new ODMCommand\Schema\DropCommand,
                    new ODMCommand\Schema\UpdateCommand,
                ]
            );
        }

        return $application;
    }

    /**
     * Retrieve entity manager.
     *
     * @param array|EntityManager $entityManager
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\ORMException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     *
     * @return EntityManager
     */
    protected static function getEntityManager($entityManager)
    {
        if (is_array($entityManager)) {
            $entityManager = EntityManagerBuilder::build($entityManager);
        }

        if (!$entityManager instanceof EntityManager) {
            throw new \InvalidArgumentException('Invalid Entity Manager provided');
        }

        return $entityManager;
    }

    /**
     * Retrieve document manager.
     *
     * @param array|DocumentManager $documentManager
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     *
     * @return DocumentManager
     */
    protected static function getDocumentManager($documentManager)
    {
        if (is_array($documentManager)) {
            $documentManager = DocumentManagerBuilder::build($documentManager);
        }

        if (!$documentManager instanceof DocumentManager) {
            throw new \InvalidArgumentException('Invalid Document Manager provided');
        }

        return $documentManager;
    }
}
