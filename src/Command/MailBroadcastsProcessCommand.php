<?php

namespace App\Command;

use App\Entity\Mail\AudienceCriteriaOperator;
use App\Entity\Mail\Broadcast;
use App\Entity\Mail\BroadcastStatus;
use App\Entity\Mail\Dispatch;
use App\Entity\Mail\DispatchStatus;
use App\Repository\Mail\BroadcastRepository;
use App\Repository\User\UserRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Stopwatch\Stopwatch;

#[AsCommand(
    name: 'app:mail:broadcasts-process',
    description: 'Generates Dispatch records for Broadcasts',
)]
class MailBroadcastsProcessCommand extends Command
{
    public function __construct(
        private BroadcastRepository $broadcastRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private LockFactory $lockFactory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'status',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Status(es) of the broadcasts to process',
                [
                    BroadcastStatus::ToProcess->value,
                    BroadcastStatus::InProcess->value,
                ]
            )
            ->addOption(
                'dispatch-limit',
                null,
                InputOption::VALUE_OPTIONAL,
                'Maximum number of dispatches per broadcast to generate in this execution',
                100000
            )
            ->addOption(
                'batch-size',
                null,
                InputOption::VALUE_OPTIONAL,
                'Maximum number of dispatches per insert',
                20000
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $this->getName();

        $io->title(sprintf(
            '%s @ %s',
            $name,
            (new \DateTime())->format(\DateTime::ISO8601_EXPANDED)
        ));

        $stopwatch = new Stopwatch();
        $stopwatch->start($name);

        $lock = $this->lockFactory->createLock($name);
        if (!$lock->acquire(true)) {
            $io->warning('Another instance of this command is already running.');

            return Command::FAILURE;
        }

        try {
            $statuses = $this->getStatuses($input);
            if ($statuses === []) {
                $io->warning('No valid broadcast statuses were provided.');

                return Command::FAILURE;
            }

            $dispatchLimit = max(1, (int) $input->getOption('dispatch-limit'));
            $batchSize = max(1, (int) $input->getOption('batch-size'));
            $processedBroadcasts = 0;
            $generatedDispatches = 0;

            $broadcasts = $this->broadcastRepository->findBy(['status' => $statuses]);
            foreach ($broadcasts as $broadcast) {
                $scheduledAt = $broadcast->getScheduledAt();
                if ($scheduledAt !== null && $scheduledAt > new \DateTimeImmutable()) {
                    $io->text(sprintf(
                        'Broadcast %d is scheduled for %s. Skipping.',
                        $broadcast->getId(),
                        $scheduledAt->format(\DateTime::ISO8601_EXPANDED)
                    ));

                    continue;
                }

                $io->section(sprintf('Processing broadcast %d', $broadcast->getId()));

                $result = $this->processBroadcast($broadcast, $dispatchLimit, $batchSize, $io);

                $generatedDispatches += $result['generated'];
                ++$processedBroadcasts;
            }

            $event = $stopwatch->stop($name);

            $io->success(sprintf(
                'Processed %d broadcasts and generated %d dispatches in %.2f seconds.',
                $processedBroadcasts,
                $generatedDispatches,
                $event->getDuration() / 1000
            ));

            return Command::SUCCESS;
        } finally {
            $lock->release();
        }
    }

    /**
     * Process a broadcast in bounded batches without creating Dispatch entities.
     *
     * @return array{generated: int}
     */
    private function processBroadcast(
        Broadcast $broadcast,
        int $dispatchLimit,
        int $batchSize,
        SymfonyStyle $io,
    ): array {
        $connection = $this->entityManager->getConnection();

        $broadcastId = $broadcast->getId();
        if ($broadcastId === null) {
            throw new \LogicException('Cannot process a broadcast without an ID.');
        }

        $generated = 0;
        while ($generated < $dispatchLimit) {
            $remaining = $dispatchLimit - $generated;

            $userIds = $this->findUserIdsByBroadcast($broadcast, min($batchSize, $remaining));
            if ($userIds === []) {
                $this->entityManager->wrapInTransaction(
                    function (EntityManagerInterface $em) use ($broadcast): void {
                        $broadcast->setStatus(BroadcastStatus::Processed);

                        $em->flush();
                    }
                );

                $io->text(sprintf(
                    'Broadcast %d is fully processed.',
                    $broadcastId
                ));

                break;
            }

            $connection->transactional(
                function (Connection $connection) use (
                    $broadcast,
                    $broadcastId,
                    $userIds,
                ): void {
                    $this->insertDispatchBatch(
                        $connection,
                        $broadcastId,
                        $userIds
                    );

                    $broadcast->setStatus(BroadcastStatus::InProcess);

                    $this->entityManager->flush();
                }
            );

            $generated += count($userIds);

            $io->text(sprintf(
                'Broadcast %d: inserted %d dispatches. Total: %d/%d.',
                $broadcastId,
                count($userIds),
                $generated,
                $dispatchLimit
            ));
        }

        return [
            'generated' => $generated,
        ];
    }

    /**
     * Return eligible user IDs.
     *
     * This uses ORM DQL to preserve your existing audience criteria,
     * but hydrates only scalar IDs.
     *
     * @return int[]
     */
    public function findUserIdsByBroadcast(Broadcast $broadcast, int $limit): array
    {
        $qb = $this->userRepository->createQueryBuilder('user')
            ->select('user.id')
            ->orderBy('user.id', 'ASC')
            ->setMaxResults($limit);

        foreach ($broadcast->getAudience()->getCriteria() as $criteria) {
            $subject = $criteria->subject;
            $referent = $criteria->referent;

            $expr = match ($criteria->operator) {
                AudienceCriteriaOperator::Equals => $qb->expr()->eq($subject, $referent),

                AudienceCriteriaOperator::GreaterThan => $qb->expr()->gt($subject, $referent),

                AudienceCriteriaOperator::GreaterThanOrEqual => $qb->expr()->gte($subject, $referent),

                AudienceCriteriaOperator::LessThan => $qb->expr()->lt($subject, $referent),

                AudienceCriteriaOperator::LessThanOrEqual => $qb->expr()->lte($subject, $referent),
            };

            $qb->andWhere($expr);
        }

        $haveDispatch = $this->entityManager->createQueryBuilder()
            ->select('1')
            ->from(Dispatch::class, 'dispatch')
            ->where('dispatch.broadcast = :broadcast')
            ->andWhere('dispatch.user = user');

        $qb->andWhere(
            $qb->expr()->not($qb->expr()->exists($haveDispatch->getDQL()))
        )->setParameter('broadcast', $broadcast);

        return array_map(
            static fn(array $row): int => (int) $row['id'],
            $qb->getQuery()->getScalarResult()
        );
    }

    /**
     * Insert a batch directly into the database using DBAL.
     *
     * No Dispatch entities are created or managed by Doctrine ORM.
     *
     * @param int[] $userIds
     */
    private function insertDispatchBatch(Connection $connection, int $broadcastId, array $userIds): void
    {
        if ($userIds === []) {
            return;
        }

        $values = [];
        $parameters = [];
        $status = DispatchStatus::InPending->value;

        foreach ($userIds as $userId) {
            $values[] = '(?, ?, ?)';

            $parameters[] = $broadcastId;
            $parameters[] = $userId;
            $parameters[] = $status;
        }

        $connection->executeStatement(
            sprintf('INSERT INTO dispatch (broadcast_id, user_id, status) VALUES %s', implode(', ', $values)),
            $parameters
        );
    }

    /**
     * @return BroadcastStatus[]
     */
    private function getStatuses(InputInterface $input): array
    {
        $statuses = [];

        foreach ($input->getOption('status') as $option) {
            $status = BroadcastStatus::tryFrom($option);

            if ($status !== null) {
                $statuses[] = $status;
            }
        }

        return $statuses;
    }
}
