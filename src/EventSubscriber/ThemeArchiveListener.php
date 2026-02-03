<?php

namespace App\EventSubscriber;

use App\Entity\Theme;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

/**
 * Écoute les modifications sur Theme pour archiver automatiquement les cartes et médias
 */
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Theme::class)]
class ThemeArchiveListener
{
    public function postUpdate(Theme $theme, LifecycleEventArgs $event): void
    {
        $entityManager = $event->getObjectManager();
        $changeSet = $entityManager->getUnitOfWork()->getEntityChangeSet($theme);

        // Si le champ 'archived' a changé
        if (isset($changeSet['archived'])) {
            $isArchived = $theme->isArchived();

            // Archiver/désarchiver toutes les cartes
            foreach ($theme->getCards() as $card) {
                $card->setArchived($isArchived);
            }

            // Archiver/désarchiver tous les médias
            foreach ($theme->getMedias() as $media) {
                $media->setArchived($isArchived);
            }

            $entityManager->flush();
        }
    }
}