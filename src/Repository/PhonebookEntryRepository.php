<?php

namespace App\Repository;

use App\Entity\PhonebookEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PhonebookEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method PhonebookEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method PhonebookEntry[]    findAll()
 * @method PhonebookEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PhonebookEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PhonebookEntry::class);
    }

    public function findFriendPhonebookEntries($userId): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select([
                'u', 'u2', 'pe'
            ])
            ->from('App\Entity\User', 'u')
            ->innerJoin('u.myFriends', 'u2')
            ->innerJoin('u2.phonebookEntry', 'pe')
            ->where('u.id = :userId')
            ->setParameter('userId', $userId);
        $query = $qb->getQuery();

        return $query->execute();

    }

}
