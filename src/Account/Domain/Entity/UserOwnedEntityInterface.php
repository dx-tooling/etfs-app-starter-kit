<?php

declare(strict_types=1);

namespace App\Account\Domain\Entity;

/**
 * Interface for entities that belong to a User.
 *
 * This interface intentionally only exposes the user ID, not the User entity itself.
 * This ensures clean separation between verticals - code outside the Account namespace
 * should not have direct access to the User entity.
 *
 * Entities implementing this interface may have their own getUser() method
 * for internal use within the Account namespace.
 */
interface UserOwnedEntityInterface
{
    public function getId(): ?string;

    public function getUserId(): string;
}
