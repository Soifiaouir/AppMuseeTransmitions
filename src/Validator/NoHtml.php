<?php
namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
class NoHtml extends Constraint
{
    public string $message = 'Ce champ contient du contenu HTML ou JavaScript interdit.';
}
