<?php

namespace App\Organization\Domain\Service;

use App\Organization\Domain\Entity\Group;
use App\Organization\Domain\Entity\Invitation;
use App\Organization\Domain\Entity\Organization;
use App\Organization\Domain\Enum\AccessRight;
use App\Shared\Domain\Enum\Iso639_1Code;

interface OrganizationDomainServiceInterface
{
    public function getAllOrganizationsForUser(
        string $userId
    ): array;

    public function userHasJoinedOrganizations(string $userId): bool;

    public function userHasJoinedOrganization(string $userId, string $organizationId): bool;

    public function userCanCreateOrManageOrganization(string $userId): bool;

    public function getOrganizationById(string $organizationId): ?Organization;

    public function createOrganization(string $userId, ?string $name = null): Organization;

    public function renameOrganization(Organization $organization, ?string $name): void;

    public function emailCanBeInvitedToOrganization(
        string       $email,
        Organization $organization
    ): bool;

    public function inviteEmailToOrganization(
        string       $email,
        Organization $organization
    ): ?Invitation;

    public function acceptInvitation(
        Invitation $invitation,
        ?string    $userId
    ): ?string;

    public function getOrganizationName(
        Organization  $organization,
        ?Iso639_1Code $iso639_1Code,
    ): string;

    public function hasPendingInvitations(
        Organization $organization
    ): bool;

    public function getPendingInvitations(
        Organization $organization
    ): array;

    public function resendInvitation(Invitation $invitation): void;

    /** @return string[] */
    public function getAllUserIdsForOrganization(Organization $organization): array;

    public function getGroupName(
        Group        $group,
        Iso639_1Code $iso639_1Code,
    ): string;

    public function getGroups(
        Organization $organization
    ): array;

    public function getGroupsOfUserForCurrentlyActiveOrganization(
        string $userId
    ): array;

    public function getDefaultGroupForNewMembers(
        Organization $organization
    ): Group;

    /** @return string[] */
    public function getGroupMemberIds(Group $group): array;

    public function addUserToGroup(string $userId, Group $group): void;

    public function removeUserFromGroup(string $userId, Group $group): void;

    public function getGroupById(string $groupId): ?Group;

    public function moveUserToAdministratorsGroup(
        string       $userId,
        Organization $organization
    ): void;

    public function moveUserToTeamMembersGroup(
        string       $userId,
        Organization $organization
    ): void;

    public function userHasAccessRight(
        string      $userId,
        AccessRight $accessRight
    ): bool;

    public function currentlyActiveOrganizationIsOwnOrganization(
        string $userId
    ): bool;

    public function userCanSwitchOrganizations(string $userId): bool;

    /** @return Organization[] */
    public function organizationsUserCanSwitchTo(string $userId): array;

    public function switchOrganization(
        string       $userId,
        Organization $organization
    ): void;
}
