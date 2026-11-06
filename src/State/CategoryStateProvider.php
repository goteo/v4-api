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
        $queryBuilder = $this->categoryRepository->getByIdOrSlugQuery($uriVariables['idOrSlug']);
        $query = $this->addLocalizationHints($queryBuilder, $this->getAcceptedLanguages($context));
        $category = $query->getOneOrNullResult();

        if ($category === null) {
            return null;
        }

        return $this->autoMapper->map($category, CategoryApiResource::class);
    }
}
