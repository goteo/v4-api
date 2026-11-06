<?php

namespace App\Repository;

use App\Entity\Address;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Address>
 */
class AddressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Address::class);
    }

    /**
     * Unsets the default flag on every Address of the given User except the given one.
     */
    public function unsetDefaultsOfUser(User $user, ?Address $except): void
    {
        $query = $this->createQueryBuilder('a')
            ->update()
            ->set('a.isDefault', ':false')
            ->where('a.user = :user')
            ->setParameter('false', false)
            ->setParameter('user', $user)
        ;

        if ($except?->getId() !== null) {
            $query
                ->andWhere('a.id != :except')
                ->setParameter('except', $except->getId())
            ;
        }

        $query->getQuery()->execute();
    }
}
