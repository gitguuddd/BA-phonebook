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
                'u', 'u2'
            ])
            ->from('App\Entity\User', 'u')
            ->innerJoin('u.myFriends', 'u2')
            ->where('u.id = :userId')
            ->setParameter('userId', $userId);
        $query = $qb->getQuery();
        $resultIds = [];
        $results = array((array)$query->getScalarResult());
        if (!is_null($results[0])) {
            foreach ($results[0] as $result) {
                $resultIds[] = $result["u2_id"];
            }
        }

        $qb2 = $this->getEntityManager()->createQueryBuilder();
        $qb2
            ->select(
                'pe')
            ->from('App\Entity\PhonebookEntry', 'pe')
            ->where('IDENTITY(pe.user) IN(:resultIds)')
            ->andWhere('IDENTITY(pe.user) != :userId')
            ->setParameter('userId', $userId)
            ->setParameter('resultIds', $resultIds);
        $query = $qb2->getQuery();
        //FIXME: VERY HACKY, but works for now
        return $query->execute();

    }

}
