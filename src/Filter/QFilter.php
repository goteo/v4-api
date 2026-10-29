<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\Mapping\ClassMetadata;
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
 * To enable queries like `?q=quux` that transform into SQL like `WHERE (foo LIKE %quux% OR id IN (SELECT ... FROM bar WHERE baz LIKE %quux%))`
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
        $metadata = $this->getClassMetadata($resourceClass);

        $or = $expr->orX();
        $subqueryFields = [];
        foreach ($this->properties[self::PARAMETER] as $field) {
            if (!$this->isPropertyNested($field, $resourceClass)) {
                if ($this->isPropertyMapped($field, $resourceClass)) {
                    $or->add($expr->like(\sprintf('%s.%s', $rootAlias, $field), ':'.$parameter));
                }
                continue;
            }

            [$association, $fieldName] = \explode('.', $field, 2);
            if (!\str_contains($fieldName, '.') && $this->isSubqueryable($metadata, $association)) {
                $subqueryFields[$association][] = $fieldName;
                continue;
            }

            // ponytail: deeper paths or owning-side associations keep the slow LEFT JOIN, add a subquery shape when needed
            [$alias, $fieldName] = $this->addJoinsForNestedProperty(
                $field,
                $rootAlias,
                $queryBuilder,
                $queryNameGenerator,
                $resourceClass,
                Join::LEFT_JOIN
            );
            $or->add($expr->like(\sprintf('%s.%s', $alias, $fieldName), ':'.$parameter));
        }

        // `root.id IN (SELECT IDENTITY(a.root) FROM Assoc a WHERE ...)` lets the database scan each table once,
        // instead of LIKE-ing across LEFT JOINs which forces a full joined scan (and a doubled one when paginating)
        foreach ($subqueryFields as $association => $fields) {
            $alias = $queryNameGenerator->generateJoinAlias($association);
            $subOr = $expr->orX();
            foreach ($fields as $fieldName) {
                $subOr->add($expr->like(\sprintf('%s.%s', $alias, $fieldName), ':'.$parameter));
            }

            $subquery = $queryBuilder->getEntityManager()->createQueryBuilder()
                ->select(\sprintf('IDENTITY(%s.%s)', $alias, $metadata->getAssociationMappedByTargetField($association)))
                ->from($metadata->getAssociationTargetClass($association), $alias)
                ->where($subOr);

            $or->add($expr->in(
                \sprintf('%s.%s', $rootAlias, $metadata->getSingleIdentifierFieldName()),
                $subquery->getDQL()
            ));
        }

        if ($or->count() === 0) {
            return;
        }

        $queryBuilder
            ->andWhere($or)
            ->setParameter($parameter, "%{$value}%");
    }

    /**
     * Inverse-side associations whose owning side is a to-one can be resolved with a subquery on the owning table.
     */
    private function isSubqueryable(ClassMetadata $metadata, string $association): bool
    {
        if (!$metadata->hasAssociation($association) || $metadata->getAssociationMapping($association)->isOwningSide()) {
            return false;
        }

        return $this->getClassMetadata($metadata->getAssociationTargetClass($association))
            ->isSingleValuedAssociation($metadata->getAssociationMappedByTargetField($association));
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
