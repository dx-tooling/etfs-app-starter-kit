<?php

declare(strict_types=1);

namespace App\Account\Presentation\Service;

use App\Account\Domain\Entity\AccountCore;

interface AccountPresentationServiceInterface
{
    public function sendVerificationEmailForClaimedAccountCore(
        AccountCore $accountCore
    ): void;

    public function sendPasswordResetEmail(
        string $email
    ): void;
}
