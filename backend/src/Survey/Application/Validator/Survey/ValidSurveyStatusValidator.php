<?php

namespace App\Survey\Application\Validator\Survey;

use App\Survey\Domain\Repository\SurveyStatusRepositoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidSurveyStatusValidator extends ConstraintValidator
{
    public function __construct(
        private SurveyStatusRepositoryInterface $surveyStatusRepository,
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

        $status = $this->surveyStatusRepository->findOneByName($value);

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
