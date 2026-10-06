<?php

namespace App\EventListener;

use App\Entity\Accounting\Transaction;
use App\Entity\EmbeddableMoney;
use App\Money\MoneyService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::onFlush)]
class AccountingBalanceListener
{
    public function __construct(
        private MoneyService $moneyService,
    ) {}

    public function onFlush(OnFlushEventArgs $event): void
    {
        $em = $event->getObjectManager();

        if (!$em instanceof EntityManagerInterface) {
            return;
        }

        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (!$entity instanceof Transaction) {
                continue;
            }

            $this->updateBalances($em, $entity);
        }
    }

    private function updateBalances(EntityManagerInterface $em, Transaction $transaction): void
    {
        $money = $transaction->getMoney();

        $origin = $transaction->getOrigin();
        $origin->setBalance(EmbeddableMoney::of(
            $this->moneyService->substract($money, $origin->getBalance())
        ));

        $target = $transaction->getTarget();
        $target->setBalance(EmbeddableMoney::of(
            $this->moneyService->add($money, $target->getBalance())
        ));

        $uow = $em->getUnitOfWork();

        $originMetadata = $em->getClassMetadata($origin::class);
        $targetMetadata = $em->getClassMetadata($target::class);

        $uow->computeChangeSet($originMetadata, $origin);
        $uow->computeChangeSet($targetMetadata, $target);
    }
}
