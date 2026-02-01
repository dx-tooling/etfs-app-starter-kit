<?php

declare(strict_types=1);

namespace App\Account\Infrastructure\SymfonyEvent;

use App\Account\Domain\Entity\AccountCore;

readonly class AccountCoreVerifiedSymfonyEvent
{
    public function __construct(
        private(set) AccountCore $accountCore
    ) {
    }
}
