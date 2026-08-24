<?php

namespace App\Shared\Infrastructure\Clock;

use App\Shared\Domain\Clock\ClockInterface;

class SystemClock implements ClockInterface
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
