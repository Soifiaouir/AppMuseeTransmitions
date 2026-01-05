<?php
// src/Service/MediaUploader.php

namespace App\Service;

use App\Entity\Media;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaUploader
{
    private const MEDIA_TYPES = [
        'image' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'],
        'video' => ['video/mp4', 'video/mpeg', 'video/webm'],
        'audio' => ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg', 'audio/webm']
    ];

    public function __construct(
        private string $mediaDirectory
    ) {
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
            $type = $this->detectMediaType($mimeType);
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
        $filePath = $this->mediaDirectory . '/' . $media->getFilePath();

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    private function detectMediaType(string $mimeType): ?string
    {
        foreach (self::MEDIA_TYPES as $type => $mimes) {
            if (in_array($mimeType, $mimes, true)) {
                return $type;
            }
        }
        return null;
    }

    public function getMediaDirectory(): string
    {
        return $this->mediaDirectory;
    }
}