<?php

declare(strict_types=1);

namespace App\Account\Facade;

use App\Account\Domain\Entity\User;
use App\Account\Domain\Service\AccountDomainServiceInterface;
use App\Account\Facade\Dto\ResultDto;
use App\Account\Facade\Dto\UserInfoDto;
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
            $user = $this->accountDomainService->register(
                $dto->emailAddress,
                $dto->plainPassword
            );

            return new ResultDto(true, null, $user->getId());
        } catch (Throwable $t) {
            return new ResultDto(false, $t->getMessage());
        }
    }

    public function getUserIdByEmail(string $email): ?string
    {
        $user = $this
            ->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => trim(mb_strtolower($email))]);

        return $user?->getId();
    }

    public function userWithIdExists(string $userId): bool
    {
        return $this->entityManager->getRepository(User::class)->find($userId) !== null;
    }

    public function getCurrentlyActiveOrganizationIdForUser(string $userId): ?string
    {
        /** @var ?User $user */
        $user = $this->entityManager->getRepository(User::class)->find($userId);

        if (is_null($user)) {
            return null;
        }

        return $user->getCurrentlyActiveOrganizationId();
    }

    public function getUserNameOrEmailById(string $userId): ?string
    {
        /** @var ?User $user */
        $user = $this->entityManager->getRepository(User::class)->find($userId);

        if ($user === null) {
            return null;
        }

        return $user->getName() ?? $user->getEmail();
    }

    /**
     * @param string[] $userIds
     *
     * @return UserInfoDto[]
     */
    public function getUserInfoByIds(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $users = $this->entityManager
            ->getRepository(User::class)
            ->findBy(['id' => $userIds]);

        $result = [];
        foreach ($users as $user) {
            $result[] = new UserInfoDto(
                $user->getId(),
                $user->getEmail(),
                $user->getName(),
                $user->getCreatedAt(),
                $user->getCurrentlyActiveOrganizationId()
            );
        }

        return $result;
    }

    public function getUserForLogin(string $userId): ?UserInterface
    {
        return $this->entityManager->getRepository(User::class)->find($userId);
    }

    public function getLoggedInUserInfo(UserInterface $user): ?UserInfoDto
    {
        $email = $user->getUserIdentifier();

        /** @var User|null $userEntity */
        $userEntity = $this
            ->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => trim(mb_strtolower($email))]);

        if ($userEntity === null) {
            return null;
        }

        return new UserInfoDto(
            $userEntity->getId(),
            $userEntity->getEmail(),
            $userEntity->getName(),
            $userEntity->getCreatedAt(),
            $userEntity->getCurrentlyActiveOrganizationId()
        );
    }
}
