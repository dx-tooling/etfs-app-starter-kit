<?php

namespace App\Account\Domain\Service;

use App\Account\Domain\Entity\User;
use Symfony\Component\HttpFoundation\Request;

interface AccountDomainServiceInterface
{
    public function createRegisteredUser(
        string  $email,
        ?string $plainPassword = null,
        bool    $isVerified = false,
        ?User   $user = null
    ): User;

    public function createUnregisteredUser(
        bool $asExtensionOnlyUser = false
    ): User;

    public function handleUnregisteredUserClaimsEmail(
        User    $claimingUser,
        string  $claimedEmail,
        ?string $plainPassword
    ): bool;

    public function handleUnregisteredUserReclaimsEmail(
        User $userToClaim
    ): void;

    public function unregisteredUserClaimsRegisteredUser(
        User $claimingUser,
        User $claimedUser
    ): bool;

    public function userMustVerifyEmailBeforeUsingSite(
        User $user
    ): bool;

    public function handleVerificationRequest(
        Request $request,
        User    $user
    ): void;

    public function makeUserVerified(
        User $user
    ): bool;

    public function updatePassword(
        User   $user,
        string $plainPassword
    ): void;

    public function userCanSignIn(
        ?User $user
    ): bool;

    public function userCanSignUp(
        ?User $user
    ): bool;

    public function userCanSignOut(
        ?User $user
    ): bool;

    public function userIsSignedIn(
        ?User $user
    ): bool;
}
