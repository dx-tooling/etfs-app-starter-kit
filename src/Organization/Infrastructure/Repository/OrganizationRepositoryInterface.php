<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Repository;

use App\Organization\Domain\Entity\Organization;

interface OrganizationRepositoryInterface
{
    /** @return Organization[] */
    public function getAllOrganizationsForUser(string $userId): array;

    public function userHasJoinedOrganizations(string $userId): bool;

    public function userHasJoinedOrganization(string $userId, string $organizationId): bool;

    public function findById(string $organizationId): ?Organization;

    public function addUserToOrganization(string $userId, string $organizationId): void;

    public function addMemberToGroup(string $userId, string $groupId): void;

    public function removeMemberFromGroup(string $userId, string $groupId): void;

    /** @return string[] */
    public function getMemberIdsOfGroup(string $groupId): array;

    public function userIsMemberOfGroup(string $userId, string $groupId): bool;

    /** @return string[] */
    public function getGroupIdsOfUser(string $userId): array;

    /** @return string[] */
    public function getJoinedUserIdsForOrganization(string $organizationId): array;
}
