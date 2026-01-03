<?php

namespace App\Validator;

use App\Security\InputSanitizer;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class NoHtmlValidator extends ConstraintValidator
{
    public function __construct()
    {
    }

    public function validate($value, Constraint $constraint): void
    {
        $sanitizer = new InputSanitizer();

        if ($sanitizer->containsDangerousContent($value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
