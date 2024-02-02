<?php

/*
 * (c) 2016-2024 Julián Gutiérrez <juliangut@gmail.com>
 *
 * @license BSD-3-Clause
 * @link https://github.com/juliangut/slim-doctrine
 */

declare(strict_types=1);

namespace Jgut\Slim\Doctrine\Repository;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ODM\MongoDB\Repository\RepositoryFactory;
use Doctrine\Persistence\ObjectRepository;
use Invoker\Invoker;
use Invoker\ParameterResolver\AssociativeArrayResolver;
use Invoker\ParameterResolver\Container\ParameterNameContainerResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\ParameterResolver\DefaultValueResolver;
use Invoker\ParameterResolver\ResolverChain;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use RuntimeException;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MongoDbRepositoryFactory implements RepositoryFactory
{
    /**
     * @var array<string, DocumentRepository<object>>
     */
    private array $repositoryList = [];

    public function __construct(
        private ?ContainerInterface $container = null,
    ) {}

    /**
     * @template T of object
     *
     * @param class-string<T> $documentName
     *
     * @return DocumentRepository<T>
     */
    public function getRepository(DocumentManager $documentManager, string $documentName): ObjectRepository
    {
        $repositoryHash = $this->getRepositoryHash($documentManager, $documentName);

        $this->repositoryList[$repositoryHash] ??= $this->createRepository($documentManager, $documentName);

        return $this->repositoryList[$repositoryHash];
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $documentName
     *
     * @throws MongoDBException
     * @throws RuntimeException
     *
     * @return DocumentRepository<T>
     */
    protected function createRepository(DocumentManager $documentManager, string $documentName): DocumentRepository
    {
        $classMetadata = $documentManager->getClassMetadata($documentName);
        if ($classMetadata->isEmbeddedDocument) {
            throw MongoDBException::cannotCreateRepository($documentName);
        }

        $repositoryClassName = $classMetadata->customRepositoryClassName
            ?? $documentManager->getConfiguration()
                ->getDefaultDocumentRepositoryClassName();

        if (!class_exists($repositoryClassName) || !is_a($repositoryClassName, DocumentRepository::class, true)) {
            throw new RuntimeException(sprintf(
                'Repository class "%s" should be a "%s".',
                $repositoryClassName,
                DocumentRepository::class,
            ));
        }

        $reflection = new ReflectionClass($repositoryClassName);
        $repository = $reflection->newInstanceWithoutConstructor();
        $this->getInvoker()
            ->call(
                [$repository, '__construct'],
                [
                    'dm' => $documentManager,
                    'uow' => $documentManager->getUnitOfWork(),
                    'classMetadata' => $classMetadata,
                ],
            );

        return $repository;
    }

    /**
     * @param class-string<object> $entityName
     */
    private function getRepositoryHash(DocumentManager $documentManager, string $entityName): string
    {
        return sprintf(
            '%s%s',
            $documentManager->getClassMetadata($entityName)
                ->getName(),
            spl_object_id($documentManager),
        );
    }

    protected function getInvoker(): Invoker
    {
        $resolvers = [];

        // Inject parameters by name first
        $resolvers[] = new AssociativeArrayResolver();
        if ($this->container !== null) {
            // Then inject services by type-hints for those that weren't resolved,
            $resolvers[] = new TypeHintContainerResolver($this->container);
            // Then inject services by name for those that weren't resolved,
            $resolvers[] = new ParameterNameContainerResolver($this->container);
        }
        $resolvers[] = new DefaultValueResolver();

        return new Invoker(new ResolverChain($resolvers), $this->container);
    }
}
