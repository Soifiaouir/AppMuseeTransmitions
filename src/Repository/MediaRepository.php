<?php
// src/Repository/MediaRepository.php

namespace App\Repository;

use App\Entity\Media;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Media>
 */
class MediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }

    public function getMediaByThemeWithPagination(int $page)
    {
        $qb = $this->createQueryBuilder('m');
        $qb->leftJoin('m.themes', 't');
        $qb->addGroupBy('m.id');
        $qb->addSelect('t');
        $qb->orderBy('t.dateOfCreation', 'DESC');

        $query = $qb->getQuery();
        $limit = Media::MEDIA_PER_PAGE;
        $offset = ($page - 1) * $limit;
        $query->setMaxResults($limit);
        $query->setFirstResult($offset);

        $paginator = new Paginator($query);
        return $paginator;
    }

    /**
     * Trouve les médias associés à un thème spécifique
     */
    public function findByTheme(int $themeId): array
    {

        return $this->createQueryBuilder('m')
            ->innerJoin('m.themes', 't')
            ->where('t.id = :themeId')
            ->andWhere('m.type = :type')
            ->andWhere('m.archived = :archived')
            ->setParameter('themeId', $themeId)
            ->setParameter('type', 'image')
            ->setParameter('archived', false)
            ->orderBy('m.userGivenName', 'ASC')
            ->getQuery()
            ->getResult();


    }

    /**
     * Trouve les images filtrées pour le formulaire (pour EntityType)
     */
    public function findImagesByThemeForForm(?int $themeId): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.type = :type')
            ->setParameter('type', 'image')
            ->orderBy('m.userGivenName', 'ASC');

        // Si un thème est donné, on filtre dessus
        if ($themeId !== null) {
            $qb
                ->innerJoin('m.themes', 't')
                ->andWhere('t.id = :themeId')
                ->setParameter('themeId', $themeId);
        }

        return $qb->getQuery()->getResult();
    }


    /**
     * Trouve les médias non associés (ni à une carte ni à un thème)
     */
    public function findUnassociated(): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.cards', 'c')
            ->leftJoin('m.themes', 't')
            ->andWhere('c.id IS NULL')
            ->andWhere('t.id IS NULL')
            ->orderBy('m.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un média identique existe déjà
     */
    public function findDuplicate(string $userGivenName, int $size, string $extension): ?Media
    {
        return $this->createQueryBuilder('m')
            ->where('m.userGivenName = :userGivenName')
            ->andWhere('m.size = :size')
            ->andWhere('m.extensionFile = :extension')
            ->setParameter('userGivenName', $userGivenName)
            ->setParameter('size', $size)
            ->setParameter('extension', $extension)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
    public function findImagesByTheme(int $themeId): array
    {
        $qb = $this->createQueryBuilder('m')
            ->innerJoin('m.themes', 't')
            ->where('t.id = :themeId')
            ->andWhere('m.type = :type')
            ->setParameter('themeId', $themeId)
            ->setParameter('type', 'image')
            ->orderBy('m.userGivenName', 'ASC');

        return $qb->getQuery()->getResult();
    }

}