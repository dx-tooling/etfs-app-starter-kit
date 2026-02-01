<?php

declare(strict_types=1);

namespace App\Account\Infrastructure\Repository;

use App\Account\Domain\Entity\AccountCore;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

interface AccountCoreRepositoryInterface extends PasswordUpgraderInterface
{
    public function add(
        AccountCore $entity,
        bool        $flush = false
    ): void;

    public function remove(
        AccountCore $entity,
        bool        $flush = false
    ): void;

    /**
     * Used to upgrade (rehash) the account's password automatically over time.
     */
    public function upgradePassword(
        PasswordAuthenticatedUserInterface $user,
        string                             $newHashedPassword
    ): void;
}
