<?php

namespace App\Security;

/**
 * Classe en charge de reperer les valeurs non voulue dans la vase donnée
 */
class InputSanitizer
{
    public function containsDangerousContent(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return preg_match('/<\s*script\b/i', $value)
            || preg_match('/<[^>]+>/', $value)
            || preg_match('/javascript:/i', $value);
    }
}