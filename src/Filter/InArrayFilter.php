<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

/**
 * For columns which value is a JSON array uses `JSON_CONTAINS` to search included values.
 */
final class InArrayFilter extends AbstractFilter
{
    /**
     * Will match when any of the given values is included in the array.
     */
    public const STRATEGY_OR = 'or';

    /**
     * Will match when all of the given values are included in the array.
     */
    public const STRATEGY_AND = 'and';

    protected function filterProperty(
        string $property,
        mixed $value,
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

        $strategy = $this->properties[$property] ?? self::STRATEGY_OR;

        switch ($strategy) {
            case self::STRATEGY_AND:
                $this->strategyAnd($property, $value, $queryBuilder, $queryNameGenerator);
                break;
            case self::STRATEGY_OR:
            default:
                $this->strategyOr($property, $value, $queryBuilder, $queryNameGenerator);
                break;
        }
    }

    private function strategyOr(
        string $property,
        mixed $values,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
    ): void {
        $values = array_values((array) array_filter($values, static fn($v) => $v !== null && $v !== ''));

        if ($values === []) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        $or = $queryBuilder->expr()->orX();
        foreach ($values as $i => $v) {
            $parameter = $queryNameGenerator->generateParameterName($property.$i);

            $or->add(sprintf(
                'JSON_CONTAINS(%s.%s, :%s) = 1',
                $alias,
                $property,
                $parameter
            ));

            $queryBuilder->setParameter($parameter, json_encode($v));
        }

        $queryBuilder->andWhere($or);
    }

    private function strategyAnd(
        string $property,
        mixed $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
    ): void {
        $alias = $queryBuilder->getRootAliases()[0];
        $parameter = $queryNameGenerator->generateParameterName($property);

        $queryBuilder->andWhere(sprintf(
            'JSON_CONTAINS(%s.%s, :%s) = 1',
            $alias,
            $property,
            $parameter
        ));

        $queryBuilder->setParameter($parameter, json_encode((array) $value));
    }

    public function getDescription(string $resourceClass): array
    {
        $description = [];

        foreach ($this->properties as $property => $strategy) {
            $description[$property] = [
                'property' => $property,
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'is_collection' => false,
            ];

            $description[$property.'[]'] = [
                'property' => $property,
                'type' => Type::BUILTIN_TYPE_ARRAY,
                'required' => false,
                'is_collection' => true,
            ];
        }

        return $description;
    }
}
