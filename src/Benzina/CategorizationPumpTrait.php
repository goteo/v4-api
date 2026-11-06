<?php

namespace App\Benzina;

use App\Entity\Theme;
use App\Repository\ThemeRepository;
use Symfony\Contracts\Service\Attribute\Required;

trait CategorizationPumpTrait
{
    private ThemeRepository $themeRepository;

    private const THEMES = [
        'education' => 'Educación',
        'culture' => 'Cultura',
        'journalism' => 'Periodismo',
        'science-and-tech' => 'Ciencia y Tecnología',
        'rural-and-ecology' => 'Desarrollo rural y Transición ecológica',
        'health-and-cares' => 'Salud y Cuidados',
        'sustainability' => 'Sostenibilidad',
        'democracy' => 'Derechos y Democracia',
        'social-economy' => 'Economía social',
    ];

    #[Required]
    public function setThemeRepository(ThemeRepository $themeRepository)
    {
        $this->themeRepository = $themeRepository;
    }

    public function getTheme(string $slug): Theme
    {
        $theme = $this->themeRepository->findOneBy(['slug' => $slug]);

        if (!$theme) {
            $theme = new Theme();
            $theme->setSlug($slug);
            $theme->setName(self::THEMES[$slug]);
        }

        $theme->addLocale('es');

        return $theme;
    }

    private function matchCategories(array $categories, array $names): bool
    {
        $categories = array_map(fn($c) => $c['name'], $categories);
        $matches = 0;

        foreach ($names as $name) {
            if (in_array($name, $categories)) {
                $matches = $matches + 1;
            }
        }

        return $matches === count($names);
    }

    private function mapOldCategoriesToThemes(string $socialCommitmentName, array $categories): array
    {
        $themes = [];

        if (
            $socialCommitmentName === 'Solidario'
            && $this->matchCategories($categories, ['Social'])
        ) {
            $themes[] = $this->getTheme('democracy');
        }

        if (
            $socialCommitmentName === 'Software libre'
            && $this->matchCategories($categories, ['Tecnológico', 'Científico'])
        ) {
            $themes[] = $this->getTheme('science-and-tech');
        }

        if (
            $socialCommitmentName === 'Generar empleo'
            && $this->matchCategories($categories, ['Emprendedor', 'Social'])
        ) {
            $themes[] = $this->getTheme('social-economy');
        }

        if (
            $socialCommitmentName === 'Periodismo independiente'
            && $this->matchCategories($categories, ['Comunicativo'])
        ) {
            $themes[] = $this->getTheme('journalism');
        }

        if (
            $socialCommitmentName === 'Educativo'
            && $this->matchCategories($categories, ['Educativo'])
        ) {
            $themes[] = $this->getTheme('education');
        }

        if (
            $socialCommitmentName === 'Crear cultura'
            && $this->matchCategories($categories, ['Cultural'])
        ) {
            $themes[] = $this->getTheme('culture');
        }

        if (
            $socialCommitmentName === 'Acción por el clima'
            && $this->matchCategories($categories, ['Ecológico'])
        ) {
            $themes[] = $this->getTheme('sustainability');
        }

        if (
            $socialCommitmentName === 'Datos abiertos'
            && $this->matchCategories($categories, ['Científico', 'Comunicativo'])
        ) {
            $themes[] = $this->getTheme('journalism');
        }

        if (
            $socialCommitmentName === 'Reforzar valores democráticos'
            && $this->matchCategories($categories, ['Social'])
        ) {
            $themes[] = $this->getTheme('democracy');
        }

        if (
            $socialCommitmentName === 'Igualdad de Género'
            && $this->matchCategories($categories, ['Social'])
        ) {
            $themes[] = $this->getTheme('democracy');
        }

        if (
            $socialCommitmentName === 'Salud y Cuidados'
            && $this->matchCategories($categories, ['Social'])
        ) {
            $themes[] = $this->getTheme('health-and-cares');
        }

        if (
            $socialCommitmentName === 'Energía y sostenibilidad'
            && $this->matchCategories($categories, ['Científico', 'Ecológico'])
        ) {
            $themes[] = $this->getTheme('science-and-tech');
        }

        if (
            $socialCommitmentName === 'Desarrollo agrorural'
            && $this->matchCategories($categories, ['Ecológico', 'Emprendedor'])
        ) {
            $themes[] = $this->getTheme('rural-and-ecology');
        }

        return $themes;
    }
}
