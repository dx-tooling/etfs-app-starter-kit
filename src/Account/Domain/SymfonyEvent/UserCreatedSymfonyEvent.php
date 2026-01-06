<?php

declare(strict_types=1);

namespace App\Account\Domain\SymfonyEvent;

use App\Account\Domain\Entity\User;

readonly class UserCreatedSymfonyEvent
{
    public function __construct(
        public User $user
    ) {
    }
}
