<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\ThemeApiResource;
use App\Doctrine\LocalizedExtensionTrait;
use App\Mapping\AutoMapper;
use App\Repository\ThemeRepository;

class ThemeStateProvider implements ProviderInterface
{
    use LocalizedExtensionTrait;

    public function __construct(
        private ThemeRepository $themeRepository,
        private AutoMapper $autoMapper,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $queryBuilder = $this->themeRepository->getByIdOrSlugQuery($uriVariables['idOrSlug']);
        $query = $this->addLocalizationHints($queryBuilder, $this->getAcceptedLanguages($context));
        $theme = $query->getOneOrNullResult();

        if ($theme === null) {
            return null;
        }

        return $this->autoMapper->map($theme, ThemeApiResource::class);
    }
}
