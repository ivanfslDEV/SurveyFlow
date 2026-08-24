<?php

namespace App\Shared\Domain\Clock;

interface ClockInterface
{
    public function now(): \DateTimeImmutable;
}
