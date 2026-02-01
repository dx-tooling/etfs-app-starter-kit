<?php

declare(strict_types=1);

namespace App\Account\Domain\Service;

use App\Account\Domain\Entity\AccountCore;

interface AccountDomainServiceInterface
{
    public function register(
        string  $email,
        ?string $plainPassword = null
    ): AccountCore;

    public function findByEmail(string $email): ?AccountCore;

    public function verifyPassword(
        AccountCore $accountCore,
        string      $plainPassword
    ): bool;

    public function updatePassword(
        AccountCore $accountCore,
        string      $plainPassword
    ): void;

    public function accountCoreCanSignIn(?AccountCore $accountCore): bool;

    public function accountCoreCanSignUp(?AccountCore $accountCore): bool;

    public function accountCoreCanSignOut(?AccountCore $accountCore): bool;

    public function accountCoreIsSignedIn(?AccountCore $accountCore): bool;
}
