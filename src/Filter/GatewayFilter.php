<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Gateway\Exception\MissingGatewayException;
use App\Gateway\GatewayLocator;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

final class GatewayFilter extends AbstractFilter
{
    public function __construct(
        private GatewayLocator $gatewayLocator,
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
        ) {
            return;
        }

        $values = (array) $value;
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $parameterName = ':'.$queryNameGenerator->generateParameterName($property);

        $alias = $rootAlias;
        if ($this->isPropertyNested($property, $resourceClass)) {
            [$alias] = $this->addJoinsForNestedProperty($property, $rootAlias, $queryBuilder, $queryNameGenerator, $resourceClass, Join::LEFT_JOIN);
        }

        $aliasedField = \sprintf('%s.gatewayId', $alias);

        if (\count($values) > 1) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->in($aliasedField, $parameterName))
                ->setParameter($parameterName, array_map(fn($v) => $this->getGatewayId($v), $values));

            return;
        }

        $queryBuilder
            ->andWhere($queryBuilder->expr()->eq($aliasedField, $parameterName))
            ->setParameter($parameterName, $this->getGatewayId($values[0]));
    }

    private function getGatewayId(mixed $value): string
    {
        $value = \array_slice(\explode('/', $value), -1)[0];

        try {
            $gateway = $this->gatewayLocator->get($value);

            return $gateway::getId();
        } catch (MissingGatewayException $e) {
            throw new NotFoundHttpException($e->getMessage());
        }
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
        }

        return $description;
    }
}
