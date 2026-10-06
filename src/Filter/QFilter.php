<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

/**
 * Will perform an OR strategy search of values across multiple properties.
 *
 * Add this filter to the API resource:
 * ```
 * #[ApiFilter(QFilter::class, properties: ['q' => ['foo', 'bar.baz']])]
 * ```
 * To enable queries like `?q=quux` that transform into SQL like `WHERE (foo LIKE %quux% OR bar.baz LIKE %quux%)`
 */
final class QFilter extends AbstractFilter
{
    public const PARAMETER = 'q';

    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        if ($property !== self::PARAMETER || !is_string($value) || trim($value) === '') {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $parameter = $queryNameGenerator->generateParameterName(self::PARAMETER);
        $expr = $queryBuilder->expr();

        $or = $expr->orX();
        foreach ($this->properties[self::PARAMETER] as $field) {
            if (
                !$this->isPropertyMapped($field, $resourceClass)
                && !$this->isPropertyNested($field, $resourceClass)
            ) {
                continue;
            }

            $alias = $rootAlias;
            $fieldName = $field;

            if ($this->isPropertyNested($field, $resourceClass)) {
                [$alias, $fieldName] = $this->addJoinsForNestedProperty(
                    $field,
                    $rootAlias,
                    $queryBuilder,
                    $queryNameGenerator,
                    $resourceClass,
                    Join::LEFT_JOIN
                );
            }
            $or->add($expr->like(sprintf('%s.%s', $alias, $fieldName), ':'.$parameter));
        }

        if ($or->count() === 0) {
            return;
        }

        $queryBuilder
            ->andWhere($or)
            ->setParameter($parameter, "%{$value}%");
    }

    public function getDescription(string $resourceClass): array
    {
        $description = [];

        $description[self::PARAMETER] = [
            'property' => self::PARAMETER,
            'type' => Type::BUILTIN_TYPE_STRING,
            'required' => false,
            'is_collection' => false,
            'description' => \sprintf(
                'Searches on %s',
                join(', ', array_map(
                    static fn($p) => "`$p`",
                    $this->properties[self::PARAMETER]
                ))
            ),
        ];

        return $description;
    }
}
