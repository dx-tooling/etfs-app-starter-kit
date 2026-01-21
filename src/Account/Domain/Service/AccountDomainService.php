<?php

namespace App\Account\Domain\Service;

use App\Account\Domain\Entity\User;
use App\Account\Domain\Enum\Role;
use App\Account\Domain\SymfonyEvent\UnregisteredUserClaimedRegisteredUserSymfonyEvent;
use App\Account\Domain\SymfonyEvent\UserCreatedSymfonyEvent;
use App\Account\Infrastructure\SymfonyEvent\UserVerifiedSymfonyEvent;
use App\Account\Presentation\Service\AccountPresentationServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use ValueError;

readonly class AccountDomainService implements AccountDomainServiceInterface
{
    public function __construct(
        private EntityManagerInterface              $entityManager,
        private AccountPresentationServiceInterface $presentationService,
        private VerifyEmailHelperInterface          $verifyEmailHelper,
        private EventDispatcherInterface            $eventDispatcher,
        private UserPasswordHasherInterface         $userPasswordHasher
    ) {
    }

    /**
     * @throws Exception
     */
    public function createRegisteredUser(
        string  $email,
        ?string $plainPassword = null,
        bool    $isVerified = false,
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

        $user->addRole(Role::REGISTERED_USER);

        $user->setPassword(
            $this->userPasswordHasher->hashPassword(
                $user,
                $plainPassword
            )
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(
            new UserCreatedSymfonyEvent($user)
        );

        if ($isVerified) {
            $this->makeUserVerified($user);
        }

        return $user;
    }

    /**
     * @throws Exception
     */
    public function createUnregisteredUser(): User
    {
        $user = new User();
        $user->setEmail(
            sha1(
                'fh45897z784787h!8997/%drh==iuh'
                . random_int(PHP_INT_MIN, PHP_INT_MAX)
                . random_int(PHP_INT_MIN, PHP_INT_MAX)
            )
            . '@unregistered.etfs.io'
        );

        $user->addRole(Role::UNREGISTERED_USER);

        $user->setPassword(
            password_hash(
                random_int(PHP_INT_MIN, PHP_INT_MAX),
                PASSWORD_DEFAULT
            )
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(
            new UserCreatedSymfonyEvent($user->getId())
        );

        return $user;
    }

    /**
     * @throws Exception
     */
    public function handleUnregisteredUserClaimsEmail(
        User    $claimingUser,
        string  $claimedEmail,
        ?string $plainPassword
    ): bool {
        if (!$claimingUser->isUnregistered()) {
            throw new LogicException('Only unregistered user sessions can claim.');
        }

        /** @var User|null $existingUser */
        $existingUser = $this
            ->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $claimedEmail]);

        if (!is_null($existingUser)) {
            throw new Exception("A user with email '$claimedEmail' already exists.");
        }

        $claimingUser->setEmail($claimedEmail);
        $claimingUser->removeRole(Role::UNREGISTERED_USER);
        $claimingUser->addRole(Role::REGISTERED_USER);

        if (!is_null($plainPassword)) {
            $claimingUser->setPassword(
                $this->userPasswordHasher->hashPassword(
                    $claimingUser,
                    $plainPassword
                )
            );
        }

        $claimingUser->setIsVerified(true);

        $this->entityManager->persist($claimingUser);
        $this->entityManager->flush();

        $this
            ->presentationService
            ->sendVerificationEmailForClaimedUser($claimingUser);

        return true;
    }

    public function handleUnregisteredUserReclaimsEmail(
        User $userToClaim
    ): void {
        $this
            ->presentationService
            ->sendVerificationEmailForClaimedUser($userToClaim);
    }

    public function unregisteredUserClaimsRegisteredUser(
        User $claimingUser,
        User $claimedUser
    ): bool {
        if (!$claimingUser->isUnregistered()) {
            throw new LogicException('Only unregistered user sessions can claim.');
        }

        if (!$claimedUser->isRegistered()) {
            throw new LogicException('Only registered user can be claimed.');
        }

        $this->entityManager->persist($claimingUser);
        $this->entityManager->persist($claimedUser);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(
            new UnregisteredUserClaimedRegisteredUserSymfonyEvent(
                $claimingUser,
                $claimedUser
            )
        );

        $this->entityManager->remove($claimingUser);
        $this->entityManager->flush();

        unset($claimingUser);

        return true;
    }

    public function userMustVerifyEmailBeforeUsingSite(
        User $user
    ): bool {
        return $user->isRegistered() && !$user->isVerified();
    }

    /**
     * @throws VerifyEmailExceptionInterface
     */
    public function handleVerificationRequest(
        Request $request,
        User    $user
    ): void {
        $this->verifyEmailHelper->validateEmailConfirmation(
            $request->getUri(),
            $user->getId(),
            $user->getEmail()
        );

        $this->makeUserVerified($user);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function makeUserVerified(
        User $user
    ): bool {
        if (!$user->isRegistered()) {
            throw new LogicException('Only registered user can be verified.');
        }

        if ($user->isVerified()) {
            throw new LogicException('User is already verified.');
        }

        $user->setIsVerified(true);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(
            new UserVerifiedSymfonyEvent($user)
        );

        return true;
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

    public function userCanSignIn(
        ?User $user
    ): bool {
        if (is_null($user)) {
            return true;
        }

        return $user->isUnregistered();
    }

    public function userCanSignUp(
        ?User $user
    ): bool {
        return $this->userCanSignIn($user);
    }

    public function userCanSignOut(
        ?User $user
    ): bool {
        if (is_null($user)) {
            return false;
        }

        return $user->isRegistered();
    }

    public function userIsSignedIn(
        ?User $user
    ): bool {
        return $this->userCanSignOut($user);
    }
}
