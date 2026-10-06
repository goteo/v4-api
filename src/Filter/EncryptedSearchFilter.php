<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Kyzegs\DoctrineEncryptionBundle\Attribute\BlindIndex;
use Kyzegs\DoctrineEncryptionBundle\Hashers\BlindIndexHasherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

final class EncryptedSearchFilter extends AbstractFilter
{
    use SecuredFilterTrait;

    public function __construct(
        private BlindIndexHasherInterface $blindIndexHasher,
        protected ?ManagerRegistry $managerRegistry,
        ?LoggerInterface $logger = null,
        protected ?array $properties = null,
        protected ?NameConverterInterface $nameConverter = null,
        private string $normalizer = BlindIndex::NORMALIZE_UPPERCASE,
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
            || !$this->isFilteringGranted($this->properties[$property])
        ) {
            return;
        }

        $values = (array) $value;
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $parameterName = ':'.$queryNameGenerator->generateParameterName($property);

        $alias = $rootAlias;
        if ($this->isPropertyNested($property, $resourceClass)) {
            [$alias, $field] = $this->addJoinsForNestedProperty($property, $rootAlias, $queryBuilder, $queryNameGenerator, $resourceClass, Join::LEFT_JOIN);
        }

        $aliasedField = \sprintf('%s.%sLookup', $alias, $field);

        if (\count($values) > 1) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->in($aliasedField, $parameterName))
                ->setParameter($parameterName, array_map(fn($v) => $this->getHashLookup($v), $values));

            return;
        }

        $queryBuilder
            ->andWhere($queryBuilder->expr()->eq($aliasedField, $parameterName))
            ->setParameter($parameterName, $this->getHashLookup($values[0]));
    }

    private function getHashLookup(mixed $value): string
    {
        return $this->blindIndexHasher->hash($value, $this->normalizer);
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
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'is_collection' => true,
            ];

            if ($strategy !== null) {
                $description[$property]['description'] = \sprintf('Secured by `%s`', $strategy);
                $description[$property.'[]']['description'] = \sprintf('Secured by `%s`', $strategy);
            }
        }

        return $description;
    }
}
