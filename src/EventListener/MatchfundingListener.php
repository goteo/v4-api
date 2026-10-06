<?php

namespace App\EventListener;

use App\Entity\Gateway\Charge;
use App\Entity\Project\Project;
use App\Gateway\ChargeStatus;
use App\Service\Matchfunding\MatchfundingService;
use App\Service\Project\SupportService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(
    event: Events::preUpdate,
    method: 'preUpdate',
    entity: Charge::class
)]
final class MatchfundingListener
{
    use RecomputingListenerTrait;

    public function __construct(
        private MatchfundingService $matchfunding,
        private SupportService $supportService,
    ) {}

    public function preUpdate(Charge $charge, PreUpdateEventArgs $event): void
    {
        if (!$event->hasChangedField('status')) {
            return;
        }

        if ($event->getNewValue('status') !== ChargeStatus::InCharge) {
            return;
        }

        $target = $charge->getTarget();
        if (!$target instanceof Project) {
            return;
        }

        $transactions = $this->matchfunding->match($charge);
        if ($transactions === []) {
            return;
        }

        $em = $event->getObjectManager();
        foreach ($transactions as $transaction) {
            $support = $this->supportService->getSupport($target, $charge->getCheckout()->getOrigin());
            $support = $this->supportService->withTransactions($support, [$transaction]);
            $this->computeChangeSet($em, $support);

            $matchSupport = $this->supportService->getSupport($target, $transaction->getOrigin());
            $matchSupport = $this->supportService->withTransactions($matchSupport, [$transaction]);
            $matchSupport->setAnonymous(false);
            $this->computeChangeSet($em, $matchSupport);
        }
    }
}
