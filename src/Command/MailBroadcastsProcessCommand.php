<?php

namespace App\Command;

use App\Entity\Mail\BroadcastStatus;
use App\Entity\Mail\Dispatch;
use App\Entity\Mail\DispatchStatus;
use App\Repository\Mail\BroadcastRepository;
use App\Repository\User\UserRepository;
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
                'Max amount of Dispatchs per Broadcast to attempt in this execution',
                1000
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
        $lock->acquire(true);

        $statuses = $this->getStatuses($input);
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

            $users = $this->userRepository->findByBroadcastPendings($broadcast, $input->getOption('dispatch-limit'));

            if ($users === []) {
                $broadcast->setStatus(BroadcastStatus::Processed);
                $this->entityManager->persist($broadcast);

                $io->text(sprintf(
                    'Broadcast %d is fully processed. Skipping.',
                    $broadcast->getId()
                ));

                continue;
            }

            foreach ($users as $user) {
                $dispatch = new Dispatch();
                $dispatch->setUser($user);
                $dispatch->setBroadcast($broadcast);
                $dispatch->setStatus(DispatchStatus::InPending);

                $this->entityManager->persist($dispatch);
            }

            $lastUser = $users[array_key_last($users)];

            $broadcast->setDispatchCursor($lastUser);
            $broadcast->setStatus(BroadcastStatus::InProcess);

            $this->entityManager->persist($broadcast);
            $this->entityManager->flush();
            $this->entityManager->clear();

            $io->text(sprintf(
                'Broadcast %d: generated %d dispatches. Cursor advanced to user %d.',
                $broadcast->getId(),
                count($users),
                $lastUser->getId(),
            ));
        }

        $lock->release();

        $io->success(sprintf(
            'Processed %d Broadcasts',
            count($broadcasts)
        ));
        $io->write($stopwatch->stop($name));

        return Command::SUCCESS;
    }

    private function getStatuses(InputInterface $input): array
    {
        $statuses = [];

        foreach ($input->getOption('status') as $option) {
            $statuses[] = BroadcastStatus::tryFrom($option);
        }

        return $statuses;
    }
}
