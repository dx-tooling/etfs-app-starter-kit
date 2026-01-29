<?php

declare(strict_types=1);

namespace App\Account\Domain\SymfonyEvent;

readonly class UserCreatedSymfonyEvent
{
    public function __construct(
        public string $userId
    ) {
    }
}
