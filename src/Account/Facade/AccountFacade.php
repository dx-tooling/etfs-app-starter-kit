<?php

declare(strict_types=1);

namespace App\Account\Facade;

use App\Account\Domain\Entity\AccountCore;
use App\Account\Domain\Service\AccountDomainServiceInterface;
use App\Account\Facade\Dto\AccountInfoDto;
use App\Account\Facade\Dto\ResultDto;
use App\Account\Facade\Dto\UserRegistrationDto;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Throwable;

readonly class AccountFacade implements AccountFacadeInterface
{
    public function __construct(
        private AccountDomainServiceInterface $accountDomainService,
        private EntityManagerInterface        $entityManager
    ) {
    }

    public function register(UserRegistrationDto $dto): ResultDto
    {
        try {
            $accountCore = $this->accountDomainService->register(
                (string) $dto->emailAddress,
                $dto->plainPassword,
                $dto->mustSetPassword
            );

            return new ResultDto(true, null, $accountCore->getId());
        } catch (Throwable $t) {
            return new ResultDto(false, $t->getMessage());
        }
    }

    public function getAccountCoreIdByEmail(string $email): ?string
    {
        /** @var AccountCore|null $accountCore */
        $accountCore = $this
            ->entityManager
            ->getRepository(AccountCore::class)
            ->findOneBy(['email' => trim(mb_strtolower($email))]);

        return $accountCore?->getId();
    }

    public function accountCoreWithIdExists(string $accountCoreId): bool
    {
        return $this->entityManager->getRepository(AccountCore::class)->find($accountCoreId) !== null;
    }

    public function getCurrentlyActiveOrganizationIdForAccountCore(string $accountCoreId): ?string
    {
        /** @var ?AccountCore $accountCore */
        $accountCore = $this->entityManager->getRepository(AccountCore::class)->find($accountCoreId);

        if (is_null($accountCore)) {
            return null;
        }

        return $accountCore->getCurrentlyActiveOrganizationId();
    }

    public function getAccountCoreEmailById(string $accountCoreId): ?string
    {
        /** @var ?AccountCore $accountCore */
        $accountCore = $this->entityManager->getRepository(AccountCore::class)->find($accountCoreId);

        if ($accountCore === null) {
            return null;
        }

        return $accountCore->getEmail();
    }

    /**
     * @param list<string> $accountCoreIds
     *
     * @return list<AccountInfoDto>
     */
    public function getAccountCoreInfoByIds(array $accountCoreIds): array
    {
        if (empty($accountCoreIds)) {
            return [];
        }

        /** @var list<AccountCore> $accountCores */
        $accountCores = $this->entityManager
            ->getRepository(AccountCore::class)
            ->findBy(['id' => $accountCoreIds]);

        $result = [];
        foreach ($accountCores as $accountCore) {
            $result[] = new AccountInfoDto(
                (string) $accountCore->getId(),
                $accountCore->getEmail(),
                $accountCore->getRoles(),
                $accountCore->getCreatedAt(),
                $accountCore->getCurrentlyActiveOrganizationId()
            );
        }

        return $result;
    }

    public function getAccountCoreForLogin(string $accountCoreId): ?UserInterface
    {
        return $this->entityManager->getRepository(AccountCore::class)->find($accountCoreId);
    }

    public function getLoggedInAccountCoreInfo(UserInterface $user): ?AccountInfoDto
    {
        $email = $user->getUserIdentifier();

        /** @var AccountCore|null $accountCore */
        $accountCore = $this
            ->entityManager
            ->getRepository(AccountCore::class)
            ->findOneBy(['email' => trim(mb_strtolower($email))]);

        if ($accountCore === null) {
            return null;
        }

        return new AccountInfoDto(
            (string) $accountCore->getId(),
            $accountCore->getEmail(),
            $accountCore->getRoles(),
            $accountCore->getCreatedAt(),
            $accountCore->getCurrentlyActiveOrganizationId()
        );
    }
}
