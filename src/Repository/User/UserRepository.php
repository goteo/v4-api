<?php

namespace App\Repository\User;

use App\Entity\Accounting\Accounting;
use App\Entity\Mail\AudienceCriteriaOperator;
use App\Entity\Mail\Broadcast;
use App\Entity\User\User;
use App\Repository\DedupedTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @implements PasswordUpgraderInterface<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    use DedupedTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Find an User by any of it's unique properties.
     *
     * @param string $identifier An id, email or handle
     */
    public function findOneByIdentifier(string $identifier): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.id = :val')
            ->orWhere('u.email = :val')
            ->orWhere('u.handle = :val')
            ->setParameter('val', $identifier)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByIdOrHandle(string $idOrHandle): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.id = :val')
            ->orWhere('u.handle = :val')
            ->setParameter('val', $idOrHandle)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByAccounting(Accounting $accounting): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.accounting = :val')
            ->setParameter('val', $accounting->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return User[] Returns an array of User objects
     */
    public function findLike(string $handle): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.handle LIKE :handle')
            ->setParameter('handle', \sprintf('%%%s%%', $handle))
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function countLikeHandle(string $handle): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.handle LIKE :handle')
            ->setParameter('handle', \sprintf('%%%s%%', $handle))
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Finds the next batch of Users matching a Broadcast's Audience.
     *
     * @return User[]
     */
    public function findByBroadcastPendings(Broadcast $broadcast, int $limit = 1000): array
    {
        $qb = $this->createQueryBuilder('user');

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

        if ($broadcast->getDispatchCursor() !== null) {
            $qb->andWhere('user.id > :afterId')
                ->setParameter('afterId', $broadcast->getDispatchCursor()->getId());
        }

        return $qb
            ->orderBy('user.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
