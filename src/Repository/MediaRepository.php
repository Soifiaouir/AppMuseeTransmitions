<?php
// src/Repository/MediaRepository.php

namespace App\Repository;

use App\Entity\Media;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    /**
     * Trouve tous les médias d'un type spécifique
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.type = :type')
            ->setParameter('type', $type)
            ->orderBy('m.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les médias uploadés dans les X derniers jours
     */
    public function findRecentMedias(int $days = 7): array
    {
        $date = new \DateTimeImmutable("-{$days} days");

        return $this->createQueryBuilder('m')
            ->andWhere('m.uploadedAt >= :date')
            ->setParameter('date', $date)
            ->orderBy('m.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les médias par extension
     */
    public function findByExtension(string $extension): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.extensionFile = :extension')
            ->setParameter('extension', $extension)
            ->orderBy('m.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les médias associés à une carte spécifique
     */
    public function findByCard(int $cardId): array
    {
        return $this->createQueryBuilder('m')
            ->innerJoin('m.cards', 'c')
            ->andWhere('c.id = :cardId')
            ->setParameter('cardId', $cardId)
            ->orderBy('m.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les médias associés à un thème spécifique
     */
    public function findByTheme(int $themeId): array
    {
        return $this->createQueryBuilder('m')
            ->innerJoin('m.themes', 't')
            ->andWhere('t.id = :themeId')
            ->setParameter('themeId', $themeId)
            ->orderBy('m.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
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
     * Calcule l'espace disque total utilisé par type
     */
    public function getTotalSizeByType(): array
    {
        $result = $this->createQueryBuilder('m')
            ->select('m.type, SUM(m.size) as totalSize, COUNT(m.id) as count')
            ->groupBy('m.type')
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * Compte le nombre de médias par type
     */
    public function countByType(): array
    {
        $result = $this->createQueryBuilder('m')
            ->select('m.type, COUNT(m.id) as count')
            ->groupBy('m.type')
            ->getQuery()
            ->getResult();

        // Transformer en tableau associatif
        $counts = [];
        foreach ($result as $row) {
            $counts[$row['type']] = $row['count'];
        }

        return $counts;
    }

    /**
     * Recherche de médias par nom
     */
    public function searchByName(string $searchTerm): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.userGivenName LIKE :search OR m.name LIKE :search')
            ->setParameter('search', '%' . $searchTerm . '%')
            ->orderBy('m.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}