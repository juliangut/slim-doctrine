<?php
/**
 * Slim3 Doctrine integration (https://github.com/juliangut/slim-doctrine)
 *
 * @link https://github.com/juliangut/slim-doctrine for the canonical source repository
 *
 * @license https://raw.githubusercontent.com/juliangut/slim-doctrine/master/LICENSE
 */

namespace Jgut\Slim\Doctrine;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Tools\Console\Helper\DocumentManagerHelper;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\ConsoleRunner;

/**
 * Doctrine CLI application builder.
 */
class CLIApplicationBuilder
{
    /**
     * Create a Doctrine CLI application.
     *
     * @param array|\Doctrine\ORM\EntityManager           $entityManager
     * @param array|\Doctrine\ODM\MongoDB\DocumentManager $documentManager
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\ORMException
     * @throws \InvalidArgumentException
     * @throws \LogicException
     * @throws \RuntimeException
     *
     * @return \Symfony\Component\Console\Application
     */
    public static function build($entityManager, $documentManager = null)
    {
        $entityManager = self::getEntityManager($entityManager);

        $helperSet = ConsoleRunner::createHelperSet($entityManager);
        $application = ConsoleRunner::createApplication($helperSet);

        if ($documentManager !== null) {
            $documentManager = self::getDocumentManager($documentManager);

            $helperSet->set(new DocumentManagerHelper($documentManager), 'dm');

            $application->addCommands(
                [
                    new \Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateDocumentsCommand(),
                    new \Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateHydratorsCommand(),
                    new \Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateProxiesCommand(),
                    new \Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateRepositoriesCommand(),
                    new \Doctrine\ODM\MongoDB\Tools\Console\Command\QueryCommand(),
                    new \Doctrine\ODM\MongoDB\Tools\Console\Command\ClearCache\MetadataCommand(),
                    new \Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\CreateCommand(),
                    new \Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\DropCommand(),
                    new \Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\UpdateCommand(),
                ]
            );
        }

        return $application;
    }

    /**
     * Retrieve entity manager.
     *
     * @param array|\Doctrine\ORM\EntityManager $entityManager
     *
     * @throws \InvalidArgumentException
     *
     * @return \Doctrine\ORM\EntityManager
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
     * @param array|\Doctrine\ODM\MongoDB\DocumentManager $documentManager
     *
     * @throws \InvalidArgumentException
     *
     * @return \Doctrine\ODM\MongoDB\DocumentManager
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
