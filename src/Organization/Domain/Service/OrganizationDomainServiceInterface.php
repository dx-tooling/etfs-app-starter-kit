<?php

namespace App\Organization\Domain\Service;

use App\Account\Domain\Entity\User;
use App\Organization\Domain\Entity\Group;
use App\Organization\Domain\Entity\Invitation;
use App\Organization\Domain\Entity\Organization;
use App\Organization\Domain\Enum\AccessRight;
use App\Shared\Domain\Enum\Iso639_1Code;

interface OrganizationDomainServiceInterface
{
    public function getAllOrganizationsForUser(
        User $user
    ): array;

    public function userJoinedOrganizations(
        User $user
    ): bool;

    public function userJoinedOrganization(
        User         $user,
        Organization $organization
    ): bool;

    public function userCanCreateOrManageOrganization(
        User $user
    ): bool;

    public function getCurrentlyActiveOrganizationOfUser(
        User $user
    ): Organization;

    public function createOrganization(
        User $owningUser
    ): Organization;

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
        ?User      $user
    ): ?User;

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

    public function getAllUsersOfOrganization(
        Organization $organization
    ): array;

    public function getGroupName(
        Group        $group,
        Iso639_1Code $iso639_1Code,
    ): string;

    public function getGroups(
        Organization $organization
    ): array;

    public function getGroupsOfUserForCurrentlyActiveOrganization(
        User $user
    ): array;

    public function getDefaultGroupForNewMembers(
        Organization $organization
    ): Group;

    public function getGroupMembers(
        Group $group
    ): array;

    public function moveUserToAdministratorsGroup(
        User         $user,
        Organization $organization
    ): void;

    public function moveUserToTeamMembersGroup(
        User         $user,
        Organization $organization
    ): void;

    public function userHasAccessRight(
        User        $user,
        AccessRight $accessRight
    ): bool;

    public function currentlyActiveOrganizationIsOwnOrganization(
        User $user
    ): bool;

    public function userCanSwitchOrganizations(
        User $user
    ): bool;

    public function organizationsUserCanSwitchTo(
        User $user
    ): array;

    public function switchOrganization(
        User         $user,
        Organization $organization
    ): void;
}
