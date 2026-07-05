<?php

namespace App\Validator\Survey;

use App\Repository\SurveyStatusRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidSurveyStatusValidator extends ConstraintValidator
{
    public function __construct(
        private SurveyStatusRepository $surveyStatusRepository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidSurveyStatus) {
            return;
        }

        if ($value === null) {
            return;
        }

        $status = $this->surveyStatusRepository->findOneBy(['name' => $value]);

        if (!$status) {
            $this->context->buildViolation($constraint->notFoundMessage)
                ->addViolation();
            return;
        }

        if (!$status->isActive()) {
            $this->context->buildViolation($constraint->inactiveMessage)
                ->addViolation();
        }
    }
}
