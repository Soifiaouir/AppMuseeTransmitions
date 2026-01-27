<?php

namespace App\Repository;

use App\Entity\Card;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Card>
 */
class CardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Card::class);
    }

    public function getCardByThemeWithPagination(int $page): Paginator
    {
        $dql = " select c from App\Entity\Card as c
                 LEFT JOIN c.theme as theme
                 ORDER BY c.theme.dateOfCreation DESC";

        $qb = $this->createQueryBuilder('c');
        $qb->leftJoin('c.theme', 't');
        $qb->addSelect('t');
        $qb->orderBy('t.dateOfCreation', 'DESC');

        $query = $qb->getQuery();
        $limit = Card::CARD_PER_PAGE;
        $offset = ($page - 1) * $limit;
        $query->setMaxResults($limit);
        $query->setFirstResult($offset);

        $paginator = new Paginator($query);
        return $paginator;
    }
    //    /**
    //     * @return Card[] Returns an array of Card objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Card
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
