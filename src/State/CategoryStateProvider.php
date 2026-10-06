<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\CategoryApiResource;
use App\Doctrine\LocalizedExtensionTrait;
use App\Mapping\AutoMapper;
use App\Repository\CategoryRepository;

class CategoryStateProvider implements ProviderInterface
{
    use LocalizedExtensionTrait;

    public function __construct(
        private CategoryRepository $categoryRepository,
        private AutoMapper $autoMapper,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $idOrSlug = $uriVariables['idOrSlug'];

        $queryBuilder = $this->categoryRepository->createQueryBuilder('c');
        $queryBuilder->where(\is_numeric($idOrSlug) ? 'c.id = :value' : 'c.slug = :value');
        $queryBuilder->setParameter('value', $idOrSlug);

        $query = $this->addLocalizationHints($queryBuilder, $this->getAcceptedLanguages($context));

        $project = $query->getOneOrNullResult();

        if ($project === null) {
            return null;
        }

        return $this->autoMapper->map($project, CategoryApiResource::class);
    }
}
