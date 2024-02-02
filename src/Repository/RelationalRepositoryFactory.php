<?php

/*
 * (c) 2016-2024 Julián Gutiérrez <juliangut@gmail.com>
 *
 * @license BSD-3-Clause
 * @link https://github.com/juliangut/slim-doctrine
 */

declare(strict_types=1);

namespace Jgut\Slim\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Repository\RepositoryFactory;
use Invoker\Invoker;
use Invoker\ParameterResolver\AssociativeArrayResolver;
use Invoker\ParameterResolver\Container\ParameterNameContainerResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\ParameterResolver\DefaultValueResolver;
use Invoker\ParameterResolver\ResolverChain;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use RuntimeException;

class RelationalRepositoryFactory implements RepositoryFactory
{
    /**
     * @var array<string, EntityRepository<object>>
     */
    private array $repositoryList = [];

    public function __construct(
        private ?ContainerInterface $container = null,
    ) {}

    /**
     * @template T of object
     *
     * @param class-string<T> $entityName
     *
     * @return EntityRepository<T>
     */
    public function getRepository(EntityManagerInterface $entityManager, $entityName)
    {
        $repositoryHash = $this->getRepositoryHash($entityManager, $entityName);

        $this->repositoryList[$repositoryHash] ??= $this->createRepository($entityManager, $entityName);

        return $this->repositoryList[$repositoryHash];
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $entityName
     *
     * @throws RuntimeException
     *
     * @return EntityRepository<T>
     */
    private function createRepository(EntityManagerInterface $entityManager, string $entityName): EntityRepository
    {
        $classMetadata = $entityManager->getClassMetadata($entityName);
        $repositoryClassName = $classMetadata->customRepositoryClassName
            ?? $entityManager->getConfiguration()
                ->getDefaultRepositoryClassName();

        if (!class_exists($repositoryClassName) || !is_a($repositoryClassName, EntityRepository::class, true)) {
            throw new RuntimeException(sprintf(
                'Repository class "%s" should be a "%s".',
                $repositoryClassName,
                EntityRepository::class,
            ));
        }

        $reflection = new ReflectionClass($repositoryClassName);
        $repository = $reflection->newInstanceWithoutConstructor();
        $this->getInvoker()
            ->call(
                [$repository, '__construct'],
                [
                    'em' => $entityManager,
                    'class' => $classMetadata,
                ],
            );

        return $repository;
    }

    /**
     * @param class-string<object> $entityName
     */
    private function getRepositoryHash(EntityManagerInterface $entityManager, string $entityName): string
    {
        return sprintf(
            '%s%s',
            $entityManager->getClassMetadata($entityName)
                ->getName(),
            spl_object_id($entityManager),
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
