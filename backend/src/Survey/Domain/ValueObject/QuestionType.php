<?php

namespace App\Survey\Domain\ValueObject;

enum QuestionType: string
{
    case TEXT = 'text';
    case SINGLE_CHOICE = 'single_choice';
    case MULTIPLE_CHOICE = 'multiple_choice';
    case RATING = 'rating';

    public const VALUES = [
        'text',
        'single_choice',
        'multiple_choice',
        'rating',
    ];
}
