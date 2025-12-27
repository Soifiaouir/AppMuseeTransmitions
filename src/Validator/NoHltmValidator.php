<?php

namespace App\Validator;

use App\Security\InputSanitizer;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class NoHtmlValidator extends ConstraintValidator
{
    public function __construct(private InputSanitizer $sanitizer) {}

    public function validate($value, Constraint $constraint): void
    {
        if ($this->sanitizer->containsDangerousContent($value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
