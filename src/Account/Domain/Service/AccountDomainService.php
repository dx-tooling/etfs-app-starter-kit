<?php

declare(strict_types=1);

namespace App\Account\Domain\Service;

use App\Account\Domain\Entity\AccountCore;
use App\Account\Facade\SymfonyEvent\AccountCoreCreatedSymfonyEvent;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use ValueError;

readonly class AccountDomainService implements AccountDomainServiceInterface
{
    public function __construct(
        private EntityManagerInterface      $entityManager,
        private EventDispatcherInterface    $eventDispatcher,
        private UserPasswordHasherInterface $userPasswordHasher
    ) {
    }

    /**
     * @throws Exception
     */
    public function register(
        string  $email,
        ?string $plainPassword = null,
        bool    $mustSetPassword = false
    ): AccountCore {
        $email               = trim(mb_strtolower($email));
        $existingAccountCore = $this->entityManager->getRepository(AccountCore::class)->findOneBy(
            ['email' => $email]
        );

        if (!is_null($existingAccountCore)) {
            throw new ValueError("Account with email '$email' already exists.");
        }

        if (is_null($plainPassword)) {
            $plainPassword = (string) random_int(PHP_INT_MIN, PHP_INT_MAX);
        }

        // Create a temporary AccountCore to hash the password (hasher needs UserInterface)
        $tempAccountCore = new AccountCore($email, '');
        $hashedPassword  = $this->userPasswordHasher->hashPassword($tempAccountCore, $plainPassword);

        // Create the real AccountCore with the hashed password
        $accountCore = new AccountCore($email, $hashedPassword);
        $accountCore->setMustSetPassword($mustSetPassword);

        $this->entityManager->persist($accountCore);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(
            new AccountCoreCreatedSymfonyEvent((string) $accountCore->getId())
        );

        return $accountCore;
    }

    public function findByEmail(string $email): ?AccountCore
    {
        /* @var AccountCore|null */
        return $this->entityManager->getRepository(AccountCore::class)->findOneBy(['email' => $email]);
    }

    public function verifyPassword(
        AccountCore $accountCore,
        string      $plainPassword
    ): bool {
        return $this->userPasswordHasher->isPasswordValid($accountCore, $plainPassword);
    }

    public function updatePassword(
        AccountCore $accountCore,
        string      $plainPassword
    ): void {
        $accountCore->setPasswordHash(
            $this->userPasswordHasher->hashPassword(
                $accountCore,
                $plainPassword
            )
        );
        $this->entityManager->persist($accountCore);
        $this->entityManager->flush();
    }

    public function accountCoreCanSignIn(?AccountCore $accountCore): bool
    {
        return is_null($accountCore);
    }

    public function accountCoreCanSignUp(?AccountCore $accountCore): bool
    {
        return $this->accountCoreCanSignIn($accountCore);
    }

    public function accountCoreCanSignOut(?AccountCore $accountCore): bool
    {
        return !is_null($accountCore);
    }

    public function accountCoreIsSignedIn(?AccountCore $accountCore): bool
    {
        return $this->accountCoreCanSignOut($accountCore);
    }
}
