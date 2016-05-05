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
use Doctrine\ODM\MongoDB\Tools\Console\Command\ClearCache\MetadataCommand;
use Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateDocumentsCommand;
use Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateHydratorsCommand;
use Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateProxiesCommand;
use Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateRepositoriesCommand;
use Doctrine\ODM\MongoDB\Tools\Console\Command\QueryCommand;
use Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\CreateCommand;
use Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\DropCommand;
use Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\UpdateCommand;
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
     * @param array|\Doctrine\ORM\EntityManager                $entityManager
     * @param array|\Doctrine\ODM\MongoDB\DocumentManager|null $documentManager
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\ORMException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \Symfony\Component\Console\Exception\LogicException
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
                    new GenerateDocumentsCommand,
                    new GenerateHydratorsCommand,
                    new GenerateProxiesCommand,
                    new GenerateRepositoriesCommand,
                    new QueryCommand,
                    new MetadataCommand,
                    new CreateCommand,
                    new DropCommand,
                    new UpdateCommand,
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
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\ORMException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
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
     * @throws \RuntimeException
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
