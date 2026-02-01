<?php

namespace App\Account\Facade;

use App\Account\Facade\Dto\ResultDto;
use App\Account\Facade\Dto\UserInfoDto;
use App\Account\Facade\Dto\UserRegistrationDto;

interface AccountFacadeInterface
{
    public function register(UserRegistrationDto $dto): ResultDto;

    public function getUserIdByEmail(string $email): ?string;

    public function userWithIdExists(string $userId): bool;

    public function getCurrentlyActiveOrganizationsIdForUser(string $userId): ?string;

    public function getUserNameOrEmailById(string $userId): ?string;

    /**
     * @param string[] $userIds
     *
     * @return UserInfoDto[]
     */
    public function getUserInfoByIds(array $userIds): array;
}
