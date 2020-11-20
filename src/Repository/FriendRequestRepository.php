<?php

namespace App\Repository;

use App\Entity\FriendRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FriendRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method FriendRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method FriendRequest[]    findAll()
 * @method FriendRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FriendRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FriendRequest::class);
    }

    public function findSentFriendRequests($userId, $returnScalar = false): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select([
                'fr', 'u', 'pe'
            ])
            ->from('App\Entity\FriendRequest', 'fr')
            ->innerJoin('fr.receiver', 'u')
            ->innerJoin('u.phonebookEntry', 'pe')
            ->where('IDENTITY(fr.sender) = :userId')
            ->setParameter('userId', $userId);
        $query = $qb->getQuery();
        if ($returnScalar) {
            return $query->getScalarResult();
        } else {
            return $query->execute();
        }
    }

    public function findReceivedFriendRequests($userId, $returnScalar = false): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select([
                'fr', 'u', 'pe'
            ])
            ->from('App\Entity\FriendRequest', 'fr')
            ->innerJoin('fr.sender', 'u')
            ->innerJoin('u.phonebookEntry', 'pe')
            ->where('IDENTITY(fr.receiver) = :userId')
            ->setParameter('userId', $userId);
        $query = $qb->getQuery();
        if ($returnScalar) {
            return $query->getScalarResult();
        } else {
            return $query->execute();
        }
    }
}
