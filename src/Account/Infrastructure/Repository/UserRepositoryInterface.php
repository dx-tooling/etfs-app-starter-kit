<?php

declare(strict_types=1);

namespace App\Account\Infrastructure\Repository;

use App\Account\Domain\Entity\User;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

interface UserRepositoryInterface extends PasswordUpgraderInterface
{
    public function add(
        User $entity,
        bool $flush = false
    ): void;

    public function remove(
        User $entity,
        bool $flush = false
    ): void;

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(
        PasswordAuthenticatedUserInterface $user,
        string                             $newHashedPassword
    ): void;
}
