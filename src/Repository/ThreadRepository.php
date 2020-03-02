<?php

namespace App\Repository;

use App\Entity\Thread;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NoResultException;

/**
 * @method Thread|null find($id, $lockMode = null, $lockVersion = null)
 * @method Thread|null findOneBy(array $criteria, array $orderBy = null)
 * @method Thread[]    findAll()
 * @method Thread[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ThreadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Thread::class);
    }

    public function getJoinedThreads(User $currentUser): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.owner', 'o')
            ->leftJoin('t.members', 'm')
            ->where('o.id = :currentUserId')
            ->orWhere('m.id = :currentUserId')
            ->orderBy('t.lastMessageAt', 'DESC')
            ->setParameter('currentUserId', $currentUser->getId())
            ->getQuery()
            ->getResult();
    }

    public function isMemberOf(User $currentUser, Thread $targetThread): bool
    {
        try
        {
            $this->createQueryBuilder('t')
                ->leftJoin('t.owner', 'o')
                ->leftJoin('t.members', 'm')
                ->where('t.id = :targetThreadId')
                ->andWhere('o.id = :currentUserId')
                ->orWhere('m.id = :currentUserId')
                ->setParameter('currentUserId', $currentUser->getId())
                ->setParameter('targetThreadId', $targetThread->getId())
                ->getQuery()
                ->getSingleResult();

            return true;
        }
        catch (NoResultException $ex)
        {
            return false;
        }
    }
}
