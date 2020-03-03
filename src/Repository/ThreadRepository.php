<?php

namespace App\Repository;

use App\Entity\ThreadRead;
use App\Entity\Thread;
use App\Entity\User;
use DateTime;
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

    /**
     * @param User $currentUser
     * @return Thread[]
     */
    public function getJoinedThreads(User $currentUser): array
    {
        /** @var Thread[] $threads */
        $threads = $this->createQueryBuilder('t')
            ->leftJoin('t.owner', 'o')
            ->leftJoin('t.members', 'm')
            ->where('o.id = :currentUserId')
            ->orWhere('m.id = :currentUserId')
            ->orderBy('t.lastMessageAt', 'DESC')
            ->setParameter('currentUserId', $currentUser->getId())
            ->getQuery()
            ->getResult();

        foreach ($threads as $thread)
        {
            $read = $this->getRead($currentUser, $thread);

            if ($read !== null)
            {
                $thread->setRead($read->getLastReadAt() >= $thread->getLastMessageAt());
            }
            else
            {
                $thread->setRead(false);
            }
        }

        return $threads;
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

    public function updateRead(User $currentUser, Thread $targetThread): bool
    {
        $read = $this->getRead($currentUser, $targetThread);

        if ($read === null)
        {
            $read = new ThreadRead();
            $read->setUser($currentUser);
            $read->setThread($targetThread);
            $this->getEntityManager()->persist($read);
        }

        $read->setLastReadAt(new DateTime());
        $this->getEntityManager()->flush();

        return true;
    }

    private function getRead(User $currentUser, Thread $targetThread): ?ThreadRead
    {
        try
        {
            return $this->getEntityManager()->createQueryBuilder()
                ->select('r')
                ->from(ThreadRead::class, 'r')
                ->join('r.user', 'u')
                ->join('r.thread', 't')
                ->where('u.id = :currentUserId')
                ->andWhere('t.id = :targetThreadId')
                ->setParameter('currentUserId', $currentUser->getId())
                ->setParameter('targetThreadId', $targetThread->getId())
                ->getQuery()
                ->getSingleResult();
        }
        catch (NoResultException $ex)
        {
            return null;
        }
    }
}
