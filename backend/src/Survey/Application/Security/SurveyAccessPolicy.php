<?php

namespace App\Survey\Application\Security;

use App\Identity\Application\Security\CurrentUserInterface;
use App\Survey\Domain\Entity\Survey;
use App\Survey\Domain\Exception\SurveyAccessDeniedException;

final class SurveyAccessPolicy
{
    public function __construct(
        private CurrentUserInterface $currentUser,
    ) {
    }

    public function assertCanManage(Survey $survey): void
    {
        if (!$survey->isOwnedBy($this->currentUser->id())) {
            throw new SurveyAccessDeniedException();
        }
    }
}
