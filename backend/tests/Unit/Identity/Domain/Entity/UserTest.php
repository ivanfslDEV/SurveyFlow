<?php

namespace App\Tests\Unit\Identity\Domain\Entity;

use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Exception\InvalidUserDataException;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    private const PASSWORD_HASH = '$2y$12$exampleHashForDomainTest';
    private const CREATED_AT = '2026-09-03 10:00:00';

    public function testItRegistersAnActiveUser(): void
    {
        $createdAt = new \DateTimeImmutable(self::CREATED_AT);

        $user = User::register('user@example.com', self::PASSWORD_HASH, $createdAt);

        self::assertNull($user->getId());
        self::assertSame('user@example.com', $user->getEmail());
        self::assertSame(self::PASSWORD_HASH, $user->getPasswordHash());
        self::assertSame($createdAt, $user->getCreatedAt());
        self::assertTrue($user->isActive());
    }

    public function testItNormalizesTheEmail(): void
    {
        $user = User::register(
            '  User.Name@Example.COM  ',
            self::PASSWORD_HASH,
            new \DateTimeImmutable(self::CREATED_AT),
        );

        self::assertSame('user.name@example.com', $user->getEmail());
    }

    public function testItRejectsAnInvalidEmail(): void
    {
        $this->expectException(InvalidUserDataException::class);
        $this->expectExceptionMessage('Email is invalid.');

        User::register(
            'not-an-email',
            self::PASSWORD_HASH,
            new \DateTimeImmutable(self::CREATED_AT),
        );
    }

    public function testItRejectsABlankPasswordHash(): void
    {
        $this->expectException(InvalidUserDataException::class);
        $this->expectExceptionMessage('Password hash cannot be blank.');

        User::register(
            'user@example.com',
            '   ',
            new \DateTimeImmutable(self::CREATED_AT),
        );
    }

    public function testItAssignsOnlyTheUserRoleOnRegistration(): void
    {
        $user = User::register(
            'user@example.com',
            self::PASSWORD_HASH,
            new \DateTimeImmutable(self::CREATED_AT),
        );

        self::assertSame([User::ROLE_USER], $user->getRoles());
    }

    public function testItCanBeDeactivated(): void
    {
        $user = User::register(
            'user@example.com',
            self::PASSWORD_HASH,
            new \DateTimeImmutable(self::CREATED_AT),
        );

        $user->deactivate();

        self::assertFalse($user->isActive());
    }
}
