<?php

declare(strict_types=1);

namespace App\Account\Infrastructure\Repository;

use App\Account\Domain\Entity\AccountCore;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/** @extends ServiceEntityRepository<AccountCore> */
class AccountCoreRepository extends ServiceEntityRepository implements AccountCoreRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry
    ) {
        parent::__construct($registry, AccountCore::class);
    }

    public function add(
        AccountCore $entity,
        bool        $flush = false
    ): void {
        $this
            ->getEntityManager()
            ->persist($entity);

        if ($flush) {
            $this
                ->getEntityManager()
                ->flush();
        }
    }

    public function remove(
        AccountCore $entity,
        bool        $flush = false
    ): void {
        $this
            ->getEntityManager()
            ->remove($entity);

        if ($flush) {
            $this
                ->getEntityManager()
                ->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the account's password automatically over time.
     */
    public function upgradePassword(
        PasswordAuthenticatedUserInterface $user,
        string                             $newHashedPassword
    ): void {
        if (!$user instanceof AccountCore) {
            throw new UnsupportedUserException(
                sprintf(
                    'Instances of "%s" are not supported.',
                    $user::class
                )
            );
        }

        $user->setPasswordHash($newHashedPassword);

        $this->add($user, true);
    }
}
