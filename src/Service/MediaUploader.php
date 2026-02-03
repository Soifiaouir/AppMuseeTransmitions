<?php
// src/Service/MediaUploader.php

namespace App\Service;

use App\Entity\Media;
use App\Repository\MediaRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\MimeTypes;

class MediaUploader
{
    private const MEDIA_TYPES = [
        'image' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'],
        'video' => ['video/mp4', 'video/mpeg', 'video/webm'],
        'audio' => ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg', 'audio/webm']
    ];

    public function __construct(
        private readonly string $mediaDirectory,
        private readonly MediaRepository $mediaRepository,
    ) {
    }

    /**
     * Vérifie si le fichier existe déjà en base
     */
    public function isDuplicate(UploadedFile $file, ?string $userGivenName = null): bool
    {
        $mimeType = $file->getMimeType();
        $originalName = $userGivenName ?: $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $extension = $file->guessExtension() ?: pathinfo($originalName, PATHINFO_EXTENSION);

        $duplicate = $this->mediaRepository->findDuplicate(
            $originalName,
            $fileSize,
            $extension,
        );

        return $duplicate !== null;
    }

    public function upload(UploadedFile $file, Media $media): Media
    {
        // Récupérer les infos du fichier
        $mimeType = $file->getMimeType();
        $originalName = $file->getClientOriginalName();
        $fileSize = $file->getSize();

        // Déterminer le type (détection automatique ou forcé par le form)
        $type = $media->getType();
        if (!$type || $type === 'temp') {
            $type = $this->detectMediaType($mimeType, $file);
        }

        // Extraire l'extension
        $extension = $file->guessExtension();
        if (!$extension) {
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        }

        // Générer le nom : UNIQUEMENT L'ID (pas d'extension ici)
        $idOnly = (string)$media->getId();
        $fullFileName = $idOnly . '.' . $extension;

        // Créer le répertoire si nécessaire
        $targetDirectory = $this->mediaDirectory . '/' . $type;
        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0755, true);
        }

        // Déplacer le fichier
        $file->move($targetDirectory, $fullFileName);

        // IMPORTANT : name = ID SEULEMENT (sans extension)
        $media->setName($idOnly); // Juste "1", "2", "3"...
        $media->setType($type); // "image", "video", "audio"
        $media->setExtensionFile($extension); // "jpg", "mp4"...
        $media->setSize($fileSize);

        // Si userGivenName vide, utiliser le nom original
        if (!$media->getUserGivenName()) {
            $media->setUserGivenName($originalName);
        }

        return $media;
    }

    public function delete(Media $media): void
    {
        // Construire le chemin complet : type/id.extension
        $fileName = $media->getName() . '.' . $media->getExtensionFile();
        $filePath = $this->mediaDirectory . '/' . $media->getType() . '/' . $fileName;

        // Supprimer le fichier s'il existe
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    private function detectMediaType(string $mimeType, UploadedFile $file): ?string
    {
        // 1. Vérifier le MIME fourni
        foreach (self::MEDIA_TYPES as $type => $mimes) {
            if (in_array($mimeType, $mimes, true)) {
                return $type;
            }
        }
        // 2. Fallback : deviner le type à partir du contenu
        $guesser = new MimeTypes();
        $guessedTypes = $guesser->guessMimeTypes($file->getPathname());

        foreach ($guessedTypes as $guessedMime) {
            foreach (self::MEDIA_TYPES as $type => $mimes) {
                if (in_array($guessedMime, $mimes, true)) {
                    return $type;
                }
            }
        }

        return null;
    }


    public function getMediaDirectory(): string
    {
        return $this->mediaDirectory;
    }
}