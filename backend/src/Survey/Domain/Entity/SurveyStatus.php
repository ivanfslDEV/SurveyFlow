<?php

namespace App\Survey\Domain\Entity;

class SurveyStatus
{
    public const ARCHIVED = 'archived';

    private ?int $id = null;
    private string $name;
    private bool $active;

    private function __construct(string $name, bool $active)
    {
        $this->name = $name;
        $this->active = $active;
    }

    public static function create(string $name, bool $active = true): self
    {
        return new self($name, $active);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function isArchived(): bool
    {
        return strtolower($this->name) === self::ARCHIVED;
    }
}
