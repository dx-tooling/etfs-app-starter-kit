<?php

namespace App\Account\Facade;

use App\Account\Domain\Entity\User;
use App\Account\Domain\Service\AccountDomainServiceInterface;
use App\Account\Facade\Dto\ResultDto;
use App\Account\Facade\Dto\UserCreationDto;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

readonly class AccountFacade
{
    public function __construct(
        private AccountDomainServiceInterface $accountDomainService,
        private EntityManagerInterface        $entityManager
    ) {
    }

    public function createRegisteredUser(UserCreationDto $dto): ResultDto
    {
        try {
            $this->accountDomainService->createRegisteredUser(
                $dto->emailAddress,
                $dto->plainPassword,
                $dto->isVerified
            );

            return new ResultDto(true);
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

    public function handleUserClaimsEmail(
        string  $userId,
        string  $claimedEmail,
        ?string $password = null
    ): ResultDto {
        $user = $this->entityManager->getRepository(User::class)->find($userId);

        if (is_null($user)) {
            return new ResultDto(false, 'User not found');
        }

        if ($user->isRegistered()) {
            return new ResultDto(true);
        }

        try {
            $this
                ->accountDomainService
                ->handleUnregisteredUserClaimsEmail(
                    $user,
                    $claimedEmail,
                    $password
                );

            return new ResultDto(true);
        } catch (Throwable $t) {
            return new ResultDto(false, $t->getMessage());
        }
    }

    public function getCurrentlyActiveOrganizationsIdForUser(string $userId): ?string
    {
        /** @var ?User $user */
        $user = $this->entityManager->getRepository(User::class)->find($userId);

        if (is_null($user)) {
            return null;
        }

        return $user->getCurrentlyActiveOrganizationsId();
    }
}
