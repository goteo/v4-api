<?php

namespace App\Command;

use App\ApiResource\User\UserApiResource;
use App\Entity\Mail\Dispatch;
use App\Entity\Mail\DispatchStatus;
use App\Mapping\AutoMapper;
use App\Repository\Mail\DispatchRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Stopwatch\Stopwatch;
use Twig\Environment;

#[AsCommand(
    name: 'app:mail:dispatches-process',
    description: 'Attempts sending Dispatch records',
)]
class MailDispatchesSendCommand extends Command
{
    public function __construct(
        private LockFactory $lockFactory,
        private DispatchRepository $dispatchRepository,
        private Environment $twig,
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager,
        private AutoMapper $autoMapper,
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
                'Status(es) of the dispatches to attempt sending',
                [DispatchStatus::InPending->value]
            )
            ->addOption(
                'broadcast',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'ID(s) of specific broadcasts to get dispatches',
            )
            ->addOption(
                'user',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'ID(s) of specific users to attempt sending to',
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                'Maximum number of dispatches to send in this execution. An integer between 1 and 2000',
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
        if (!$lock->acquire(true)) {
            $io->warning('Another instance of this command is already running.');

            return Command::FAILURE;
        }

        try {
            $statuses = $this->getStatuses($input);
            if ($statuses === []) {
                $io->warning('No valid dispatch statuses were provided.');

                return Command::FAILURE;
            }
            $criteria['status'] = $this->getStatuses($input);

            $broadcasts = $input->getOption('broadcast');
            if ($broadcasts !== null && $broadcasts !== []) {
                $criteria['broadcast'] = $broadcasts;
            }

            $users = $input->getOption('user');
            if ($users !== null && $users !== []) {
                $criteria['user'] = $users;
            }

            $countDispatches = 0;
            $errorDispatches = 0;

            $dispatches = $this->dispatchRepository->findBy(
                $criteria,
                null,
                min(2000, $input->getOption('limit'))
            );

            $io->text(sprintf('Processing %d dispatches', \count($dispatches)));

            foreach ($dispatches as $dispatch) {
                $dispatch = $this->processDispatch($dispatch);

                $errorDispatches += $dispatch->getStatus() === DispatchStatus::InError;
                ++$countDispatches;

                $this->entityManager->persist($dispatch);
            }

            $this->entityManager->flush();

            $event = $stopwatch->stop($name);

            $io->success(sprintf(
                'Processed %d dispatches with %d errors in %.2f seconds.',
                $countDispatches,
                $errorDispatches,
                $event->getDuration() / 1000
            ));

            return Command::SUCCESS;
        } finally {
            $lock->release();
        }
    }

    private function processDispatch(Dispatch $dispatch): Dispatch
    {
        try {
            $broadcast = $dispatch->getBroadcast();
            /** @var UserApiResource */
            $user = $this->autoMapper->map($dispatch->getUser(), UserApiResource::class);
            $body = $this->twig->createTemplate($broadcast->getBody());

            $email = new Email();
            $email->from($broadcast->getSender());
            $email->to($user->email);
            $email->subject($broadcast->getSubject());
            $email->html($body->render(['user' => $user]));

            $this->mailer->send($email);

            $dispatch->setStatus(DispatchStatus::Dispatched);
        } catch (\Exception $e) {
            $dispatch->setStatus(DispatchStatus::InError);
            $dispatch->setError(json_encode(['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]));
        }

        return $dispatch;
    }

    /**
     * @return DispatchStatus[]
     */
    private function getStatuses(InputInterface $input): array
    {
        $statuses = [];

        foreach ($input->getOption('status') as $option) {
            $status = DispatchStatus::tryFrom($option);

            if ($status !== null) {
                $statuses[] = $status;
            }
        }

        return $statuses;
    }
}
