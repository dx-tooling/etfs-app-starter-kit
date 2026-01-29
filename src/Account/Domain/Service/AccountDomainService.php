<?php

namespace App\Account\Domain\Service;

use App\Account\Domain\Entity\User;
use App\Account\Domain\SymfonyEvent\UserCreatedSymfonyEvent;
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
        ?User   $user = null
    ): User {
        $email        = trim(mb_strtolower($email));
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(
            ['email' => $email]
        );

        if (!is_null($existingUser)) {
            throw new ValueError("User with email '$email' already exists.");
        }

        if (is_null($user)) {
            $user = new User();
        }

        $user->setEmail($email);

        if (is_null($plainPassword)) {
            $plainPassword = random_int(PHP_INT_MIN, PHP_INT_MAX);
        }

        $user->setPassword(
            $this->userPasswordHasher->hashPassword(
                $user,
                $plainPassword
            )
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(
            new UserCreatedSymfonyEvent($user->getId())
        );

        return $user;
    }

    public function findByEmail(string $email): ?User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
    }

    public function verifyPassword(
        User   $user,
        string $plainPassword
    ): bool {
        return $this->userPasswordHasher->isPasswordValid($user, $plainPassword);
    }

    public function updatePassword(
        User   $user,
        string $plainPassword
    ): void {
        $user->setPassword(
            $this->userPasswordHasher->hashPassword(
                $user,
                $plainPassword
            )
        );
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function userCanSignIn(?User $user): bool
    {
        return is_null($user);
    }

    public function userCanSignUp(?User $user): bool
    {
        return $this->userCanSignIn($user);
    }

    public function userCanSignOut(?User $user): bool
    {
        return !is_null($user);
    }

    public function userIsSignedIn(?User $user): bool
    {
        return $this->userCanSignOut($user);
    }
}
