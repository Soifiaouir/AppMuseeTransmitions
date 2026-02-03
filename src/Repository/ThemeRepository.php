<?php

namespace App\Repository;

use App\Entity\Theme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Theme>
 */
class ThemeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Theme::class);
    }
    public function findALLWithPagination(int $page): Paginator
    {
        $qb = $this->createQueryBuilder('t');
        $qb->leftJoin('t.cards', 'c');
        $qb->addSelect('c');
        $qb->leftJoin('t.medias', 'm');
        $qb->addSelect('m');

        $qb->andWhere('t.archived = :archived');
        $qb->setParameter('archived', false);
        $qb->orderBy('t.dateOfCreation', 'DESC');

        $query = $qb->getQuery();
        $limit = Theme::THEME_PER_PAGE;
        $offset = ($page - 1) * $limit;
        $query->setMaxResults($limit);
        $query->setFirstResult($offset);

        return new Paginator($query, true);
    }
    public function findOneWithRelations(int $id): ?Theme
    {
        $dql = "SELECT t, c, m, bi, cb, tbc
            FROM App\Entity\Theme t
            LEFT JOIN t.cards c
            LEFT JOIN t.medias m
            LEFT JOIN t.backgroundImage bi
            LEFT JOIN t.createdBy cb
            LEFT JOIN t.themeBackgroundColor tbc
            WHERE t.id = :id";

        $qb = $this->createQueryBuilder('t');
        $qb->leftJoin('t.cards', 'c')
            ->leftJoin('t.medias', 'm')
            ->leftJoin('t.backgroundImage', 'bi')
            ->leftJoin('t.createdBy', 'cb')
            ->leftJoin('t.themeBackgroundColor', 'tbc')
            ->where('t.id = :id')
            ->setParameter('id', $id);

        $query = $qb->getQuery();
        return $query->getOneOrNullResult();
    }

}
