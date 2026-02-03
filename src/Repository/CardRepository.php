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

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getBuilder(): \Doctrine\ORM\QueryBuilder
    {
        $dql = "SELECT c, t, m, media, tc, bc FROM App\Entity\Card c
                LEFT JOIN c.theme AS t
                LEFT JOIN c.moreInfos AS m
                LEFT JOIN m.media AS media
                LEFT JOIN m.textColor AS tc
                LEFT JOIN m.backgroundColor AS bc";

        $qb = $this->createQueryBuilder('c');
        $qb->leftJoin('c.theme', 't');
        $qb->addSelect('t');
        $qb->leftJoin('c.moreInfos', 'm');
        $qb->addSelect('m');
        $qb->leftJoin('c.medias', 'media');
        $qb->addSelect('media');
        $qb->leftJoin('c.textColor', 'tc');
        $qb->addSelect('tc');
        $qb->leftJoin('c.backgroundColor', 'bc');
        $qb->addSelect('bc');
        return $qb;
    }
    public function getCardByThemeWithPagination(int $page): Paginator
    {
        $dql = " SELECT c FROM App\Entity\Card AS c
                 LEFT JOIN c.theme AS t
                 ORDER BY c.theme.dateOfCreation DESC";

        $qb = $this->getBuilder();
        $qb->orderBy('t.dateOfCreation', 'DESC');

        $query = $qb->getQuery();
        $limit = Card::CARD_PER_PAGE;
        $offset = ($page - 1) * $limit;
        $query->setMaxResults($limit);
        $query->setFirstResult($offset);

        $paginator = new Paginator($query);
        return $paginator;
    }


    public function findOneWithRelations(int $id): ?Card
    {
        $dql = "SELECT c FROM App\Entity\Card c
            LEFT JOIN c.theme t
            WHERE c.id = :id";

        $qb = $this->getBuilder();
        $qb->where('c.id = :id');
        $qb->setParameter('id', $id);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
