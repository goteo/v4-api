<?php

namespace App\Command;

use App\Entity\Project\Project;
use App\Service\Project\BudgetService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:project:budget:denormalize',
    description: 'Recalculates the budget stored with every Project from its BudgetItems. Run with --no-debug, debug mode runs out of memory on large databases',
)]
class ProjectBudgetDenormalizeCommand extends Command
{
    private const BATCH_SIZE = 100;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private BudgetService $budgetService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $ids = $this->entityManager
            ->createQuery(\sprintf('SELECT p.id FROM %s p ORDER BY p.id', Project::class))
            ->getSingleColumnResult();

        foreach (\array_chunk($ids, self::BATCH_SIZE) as $batch) {
            $projects = $this->entityManager->getRepository(Project::class)->findBy(['id' => $batch]);

            foreach ($projects as $project) {
                $project->setBudget($this->budgetService->summarize(
                    $project->getBudgetItems()->toArray(),
                    $project->getAccounting()->getCurrency()
                ));
            }

            $this->entityManager->flush();
            $this->entityManager->clear();
        }

        $io->success(\sprintf('Updated the budget of %d Projects', \count($ids)));

        return Command::SUCCESS;
    }
}
