<?php

namespace App\Benzina;

use App\Entity\EmbeddableMoney;
use App\Entity\Project\BudgetItemType;
use App\Entity\Project\Project;
use App\Entity\Project\Reward;
use App\Repository\Project\ProjectRepository;
use Goteo\Benzina\Pump\ArrayPumpTrait;
use Goteo\Benzina\Pump\PumpInterface;

class ProjectsRewardsPump implements PumpInterface
{
    use ArrayPumpTrait;
    use DatabasePumpTrait;
    use DoctrineLoggablePumpTrait;
    use LocalizedPumpTrait;

    private const REWARD_KEYS = [
        'id',
        'project',
        'reward',
        'description',
        'type',
        'icon',
        'other',
        'license',
        'amount',
        'units',
        'fulsocial',
        'url',
        'order',
        'bonus',
        'category',
        'extra_info_message',
        'subscribable',
    ];

    public function __construct(
        private ProjectRepository $projectRepository,
    ) {}

    public function supports(mixed $sample): bool
    {
        if ($this->hasAllKeys($sample, self::REWARD_KEYS)) {
            return true;
        }

        return false;
    }

    public function pump(mixed $record, array $context): void
    {
        if (empty($record['reward']) || empty($record['amount'])) {
            return;
        }

        if ($record['type'] !== 'individual') {
            return;
        }

        $project = $this->getProject($record);
        if ($project === null) {
            return;
        }

        $reward = new Reward();
        $reward->setProject($project);
        $reward->setMigrated(true);
        $reward->setMigratedId($record['id']);
        $reward->setTranslatableLocale($project->getLocales()[0]);
        $reward->setTitle($record['reward']);
        $reward->setDescription($record['description'] ?? $record['reward']);
        $reward->setMoney(new EmbeddableMoney($record['amount'] * 100, 'EUR'));
        $reward->setIsFinite($record['units'] > 0);
        $reward->setUnitsTotal($record['units'] ?? 0);

        $this->setPreventFlushAndClear(true);
        $this->persist($reward, $context);

        $localizations = $this->getRewardLocalizations($reward, $context);

        $this->setPreventFlushAndClear(false);
        $this->localize($reward, $localizations, $context, [
            'title' => fn($l) => $l['reward'],
            'description' => fn($l) => $l['description'],
        ]);
    }

    private function getProject(array $record): ?Project
    {
        return $this->projectRepository->findOneBy(['migratedId' => $record['project']]);
    }

    private function getCostType(array $record): BudgetItemType
    {
        switch ($record['type']) {
            case 'task':
                return BudgetItemType::Task;
            case 'material':
                return BudgetItemType::Material;
            default:
                return BudgetItemType::Infrastructure;
        }
    }

    private function getRewardLocalizations(Reward $reward, array $context): array
    {
        $query = $this->getDbConnection($context)->prepare(
            'SELECT * FROM `reward_lang` l WHERE l.id = :reward'
        );

        $query->execute(['reward' => $reward->getMigratedId()]);

        return $query->fetchAll();
    }
}
