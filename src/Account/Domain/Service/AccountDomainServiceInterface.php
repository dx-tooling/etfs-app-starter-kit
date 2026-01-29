<?php

namespace App\Account\Domain\Service;

use App\Account\Domain\Entity\User;

interface AccountDomainServiceInterface
{
    public function register(
        string  $email,
        ?string $plainPassword = null,
        ?User   $user = null
    ): User;

    public function findByEmail(string $email): ?User;

    public function verifyPassword(
        User   $user,
        string $plainPassword
    ): bool;

    public function updatePassword(
        User   $user,
        string $plainPassword
    ): void;

    public function userCanSignIn(?User $user): bool;

    public function userCanSignUp(?User $user): bool;

    public function userCanSignOut(?User $user): bool;

    public function userIsSignedIn(?User $user): bool;
}
