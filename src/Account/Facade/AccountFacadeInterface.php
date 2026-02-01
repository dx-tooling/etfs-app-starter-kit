<?php

declare(strict_types=1);

namespace App\Account\Facade;

use App\Account\Facade\Dto\AccountInfoDto;
use App\Account\Facade\Dto\ResultDto;
use App\Account\Facade\Dto\UserRegistrationDto;
use Symfony\Component\Security\Core\User\UserInterface;

interface AccountFacadeInterface
{
    public function register(UserRegistrationDto $dto): ResultDto;

    public function getAccountCoreIdByEmail(string $email): ?string;

    public function accountCoreWithIdExists(string $accountCoreId): bool;

    public function getCurrentlyActiveOrganizationIdForAccountCore(string $accountCoreId): ?string;

    public function getAccountCoreEmailById(string $accountCoreId): ?string;

    public function mustSetPassword(string $email): bool;

    /**
     * @param list<string> $accountCoreIds
     *
     * @return list<AccountInfoDto>
     */
    public function getAccountCoreInfoByIds(array $accountCoreIds): array;

    /**
     * Returns a UserInterface for login purposes.
     */
    public function getAccountCoreForLogin(string $accountCoreId): ?UserInterface;

    /**
     * Get account info for the currently logged-in user.
     * Returns null if account not found (should not happen for authenticated users).
     */
    public function getLoggedInAccountCoreInfo(UserInterface $user): ?AccountInfoDto;
}
