<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

final class OrderedLikeFilter extends AbstractFilter
{
    public function __construct(
        protected ?ManagerRegistry $managerRegistry,
        ?LoggerInterface $logger = null,
        protected ?array $properties = null,
        protected ?NameConverterInterface $nameConverter = null,
    ) {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        if (
            !$this->isPropertyEnabled($property, $resourceClass)
            || !$this->isPropertyMapped($property, $resourceClass)
        ) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $likeParameter = $queryNameGenerator->generateParameterName($property);
        $instrParameter = $queryNameGenerator->generateParameterName(sprintf('%s%s', $property, 'instr'));
        $positionAlias = $queryNameGenerator->generateJoinAlias('position');

        $queryBuilder
            ->andWhere(sprintf('%s.%s LIKE :%s', $rootAlias, $property, $likeParameter))
            ->addSelect(sprintf(
                'INSTR(%s.%s, :%s) AS HIDDEN %s',
                $rootAlias,
                $property,
                $instrParameter,
                $positionAlias
            ))
            ->addOrderBy($positionAlias, 'ASC')
            ->addOrderBy(sprintf('%s.%s', $rootAlias, $property), 'ASC')
            ->setParameter($likeParameter, sprintf('%%%s%%', $value))
            ->setParameter($instrParameter, $value)
        ;
    }

    public function getDescription(string $resourceClass): array
    {
        $description = [];
        foreach ($this->properties as $property => $strategy) {
            $description["$property"] = [
                'property' => $property,
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
            ];
        }

        return $description;
    }
}
