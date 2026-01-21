<?php

namespace App\Account\Facade;

use App\Account\Facade\Dto\ResultDto;
use App\Account\Facade\Dto\UserCreationDto;

interface AccountFacadeInterface
{
    public function createRegisteredUser(UserCreationDto $dto): ResultDto;

    public function getUserIdByEmail(string $email): ?string;

    public function userWithIdExists(string $userId): bool;

    public function handleUserClaimsEmail(
        string  $userId,
        string  $claimedEmail,
        ?string $password = null
    ): ResultDto;

    public function getCurrentlyActiveOrganizationsIdForUser(string $userId): ?string;
}
