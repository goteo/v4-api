<?php

namespace App\Benzina;

use Doctrine\DBAL\Logging\Middleware as LoggingMiddleware;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Loggable\LoggableListener;
use Goteo\Benzina\Pump\DoctrinePumpTrait;
use Symfony\Contracts\Service\Attribute\Required;

trait DoctrineLoggablePumpTrait
{
    use DoctrinePumpTrait;

    #[Required()]
    public function removeLoggable(EntityManagerInterface $entityManager): void
    {
        $middlewares = $entityManager->getConnection()->getConfiguration()->getMiddlewares();
        $middlewares = \array_filter($middlewares, fn($m) => !$m instanceof LoggingMiddleware);

        $entityManager->getConnection()->getConfiguration()->setMiddlewares($middlewares);

        $evm = $entityManager->getEventManager();
        foreach ($evm->getAllListeners() as $event => $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof LoggableListener) {
                    $evm->removeEventListener([$event], $listener);
                }
            }
        }

        $this->setEntityManager($entityManager);
    }
}
