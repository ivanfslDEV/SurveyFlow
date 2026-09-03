<?php

namespace App\Identity\Domain\Entity;

use App\Identity\Domain\Exception\InvalidUserDataException;

final class User
{
    public const ROLE_USER = 'ROLE_USER';

    private ?int $id = null;
    private string $email;
    private string $passwordHash;

    /**
     * @var list<string>
     */
    private array $roles;

    private bool $active;
    private \DateTimeImmutable $createdAt;

    private function __construct(
        string $email,
        string $passwordHash,
        \DateTimeImmutable $createdAt,
    ) {
        $normalizedEmail = self::normalizeEmail($email);
        self::assertValidEmail($normalizedEmail);
        self::assertValidPasswordHash($passwordHash);

        $this->email = $normalizedEmail;
        $this->passwordHash = $passwordHash;
        $this->roles = [self::ROLE_USER];
        $this->active = true;
        $this->createdAt = $createdAt;
    }

    public static function register(
        string $email,
        string $passwordHash,
        \DateTimeImmutable $createdAt,
    ): self {
        return new self($email, $passwordHash, $createdAt);
    }

    public function deactivate(): void
    {
        $this->active = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    private static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private static function assertValidEmail(string $email): void
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidUserDataException('Email is invalid.');
        }

        if (mb_strlen($email) > 180) {
            throw new InvalidUserDataException('Email cannot be longer than 180 characters.');
        }
    }

    private static function assertValidPasswordHash(string $passwordHash): void
    {
        if (trim($passwordHash) === '') {
            throw new InvalidUserDataException('Password hash cannot be blank.');
        }
    }
}
