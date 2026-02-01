<?php

declare(strict_types=1);

namespace App\Account\Presentation\Service;

use App\Account\Domain\Entity\User;

interface AccountPresentationServiceInterface
{
    public function sendVerificationEmailForClaimedUser(
        User $user
    ): void;

    public function sendPasswordResetEmail(
        string $email
    ): void;
}
