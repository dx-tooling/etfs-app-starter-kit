<?php

declare(strict_types=1);

namespace App\Account\Facade;

use App\Account\Facade\Dto\ResultDto;
use App\Account\Facade\Dto\UserInfoDto;
use App\Account\Facade\Dto\UserRegistrationDto;
use Symfony\Component\Security\Core\User\UserInterface;

interface AccountFacadeInterface
{
    public function register(UserRegistrationDto $dto): ResultDto;

    public function getUserIdByEmail(string $email): ?string;

    public function userWithIdExists(string $userId): bool;

    public function getCurrentlyActiveOrganizationIdForUser(string $userId): ?string;

    public function getUserNameOrEmailById(string $userId): ?string;

    /**
     * @param string[] $userIds
     *
     * @return UserInfoDto[]
     */
    public function getUserInfoByIds(array $userIds): array;

    /**
     * Returns a UserInterface for login purposes.
     */
    public function getUserForLogin(string $userId): ?UserInterface;

    /**
     * Get user info for the currently logged-in user.
     * Returns null if user not found (should not happen for authenticated users).
     */
    public function getLoggedInUserInfo(UserInterface $user): ?UserInfoDto;
}
