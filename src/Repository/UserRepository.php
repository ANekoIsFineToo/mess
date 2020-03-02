<?php

namespace App\Repository;

use App\Entity\Thread;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        if (!$user instanceof User)
        {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newEncodedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * Busca amigos que un usuario puede añadir a su lista de amigos a partir del fragmento de un nombre de usuario.
     *
     * @param User      $currentUser    usuario actual que esta realizando la búsqueda
     * @param string    $username       nombre de usuario a buscar
     * @param int       $maxResults     número máximo de resultados que se devolverán
     * @return User[] usuarios que coinciden con el nombre de usuario indicado
     */
    public function searchFriends(User $currentUser, string $username, int $maxResults): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.myFriends', 'mf', 'WITH', 'mf.id = :currentUserId')
            ->leftJoin('u.friendsWithMe', 'fwe', 'WITH', 'fwe.id = :currentUserId')
            ->where('u.id <> :currentUserId') // El usuario no puede ser el que está buscando
            ->andWhere('mf.id IS NULL') // El usuario no puede pertenecer a la lista de amigos
            ->andWhere('fwe.id IS NULL') // El usuario no puede ser una petición pendiente
            ->andWhere('u.username LIKE :username') // El usuario debe tener un nombre similar al que se busca
            ->setParameter('currentUserId', $currentUser->getId())
            ->setParameter('username', "%{$username}%")
            ->orderBy('u.username', 'ASC')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();
    }

    /**
     * Devuelve la lista de usuarios que son amigos confirmados de un usuario.
     *
     * @param User      $currentUser    usuario actual que esta realizando la búsqueda
     * @param int|null  $maxResults     número máximo de resultados que se devolverán
     * @return User[] usuarios que son amigos confirmados
     */
    public function getUserFriends(User $currentUser, ?int $maxResults = null): array
    {
        return $this->buildFriendsQueries($currentUser, $maxResults)
            ->where('mf.id IS NOT NULL')
            ->andWhere('fwe.id IS NOT NULL')
            ->getQuery()
            ->getResult();
    }

    /**
     * Devuelve la lista de usuarios a los que el usuario ha enviado una petición de amistad.
     *
     * @param User      $currentUser    usuario actual que esta realizando la búsqueda
     * @param int|null  $maxResults     número máximo de resultados que se devolverán
     * @return User[] usuarios a los que el usuario ha enviado una petición de amistad
     */
    public function getUserPendingSent(User $currentUser, ?int $maxResults = null): array
    {
        return $this->buildFriendsQueries($currentUser, $maxResults)
            ->where('mf.id IS NULL')
            ->andWhere('fwe.id IS NOT NULL')
            ->getQuery()
            ->getResult();
    }

    /**
     * Devuelve la lista de usuarios que han enviado una petición de amistad al usuario.
     *
     * @param User      $currentUser    usuario actual que esta realizando la búsqueda
     * @param int|null  $maxResults     número máximo de resultados que se devolverán
     * @return User[] usuarios que han enviado una petición de amistad al usuario
     */
    public function getUserPendingReceived(User $currentUser, ?int $maxResults = null): array
    {
        return $this->buildFriendsQueries($currentUser, $maxResults)
            ->where('mf.id IS NOT NULL')
            ->andWhere('fwe.id IS NULL')
            ->getQuery()
            ->getResult();
    }

    public function isFriendOf(User $currentUser, User $targetUser): bool
    {
        try
        {
            $this->createQueryBuilder('u')
                ->leftJoin('u.myFriends', 'mf', 'WITH', 'mf.id = :currentUserId')
                ->leftJoin('u.friendsWithMe', 'fwe', 'WITH', 'fwe.id = :currentUserId')
                ->where('mf.id IS NOT NULL')
                ->andWhere('fwe.id IS NOT NULL')
                ->andWhere('u.id = :targetUserId')
                ->setParameter('currentUserId', $currentUser->getId())
                ->setParameter('targetUserId', $targetUser->getId())
                ->getQuery()
                ->getSingleResult();

            return true;
        }
        catch (NoResultException $ex)
        {
            return false;
        }
    }

    /**
     * Genera la consulta básica para las posteriores consultas de amistades de los usuarios.
     *
     * @param User      $currentUser usuario actual que esta realizando la búsqueda
     * @param int|null  $maxResults número máximo de resultados que se devolverán
     * @return QueryBuilder
     */
    public function buildFriendsQueries(User $currentUser, ?int $maxResults = null): QueryBuilder
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.myFriends', 'mf', 'WITH', 'mf.id = :currentUserId')
            ->leftJoin('u.friendsWithMe', 'fwe', 'WITH', 'fwe.id = :currentUserId')
            ->setParameter('currentUserId', $currentUser->getId())
            ->orderBy('u.username', 'ASC')
            ->setMaxResults($maxResults);
    }

    public function getMembersOfThread(Thread $targetThread): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.ownedThreads', 'ot', 'WITH', 'ot.id = :targetThreadId')
            ->leftJoin('u.joinedThreads', 'jt', 'WITH', 'jt.id = :targetThreadId')
            ->where('ot.id IS NOT NULL')
            ->orWhere('jt.id IS NOT NULL')
            ->orderBy('u.username', 'ASC')
            ->setParameter('targetThreadId', $targetThread->getId())
            ->getQuery()
            ->getResult();
    }

    // /**
    //  * @return User[] Returns an array of User objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
