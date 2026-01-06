<?php

namespace App\Account\Domain\SymfonyEvent;

use App\Account\Domain\Entity\User;

readonly class UnregisteredUserClaimedRegisteredUserSymfonyEvent
{
    public function __construct(
        public User $unregisteredUser,
        public User $registeredUser
    ) {
    }
}
