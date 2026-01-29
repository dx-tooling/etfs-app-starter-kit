<?php

namespace App\Account\Infrastructure\SymfonyEvent;

use App\Account\Domain\Entity\User;

readonly class UserVerifiedSymfonyEvent
{
    public function __construct(
        private(set) User $user
    ) {
    }
}
