<?php

declare(strict_types=1);

namespace App\Account\Facade\SymfonyEvent;

readonly class AccountCoreCreatedSymfonyEvent
{
    public function __construct(
        public string $accountCoreId
    ) {
    }
}
