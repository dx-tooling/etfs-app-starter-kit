<?php

namespace App\Account\Domain\Service;

use App\Account\Domain\Entity\User;
use App\Organization\Domain\Enum\AccessRight;

readonly class CapabilitiesService
{
    public function __construct(
        private MembershipPlanService     $membershipPlanService,
        private OrganizationDomainService $organizationDomainService
    ) {
    }

    public function canSubscribeToMembershipPlans(User $user): bool
    {
        return $user->ownsCurrentlyActiveOrganization();
    }

    public function canPurchasePackages(User $user): bool
    {
        return $user->ownsCurrentlyActiveOrganization();
    }

    public function canSeeLeftNavigation(?User $user): bool
    {
        return !is_null($user);
    }

    public function canSeeTopNavigationOnLargeScreenWidth(?User $user): bool
    {
        return !is_null($user);
    }

    public function canSeeUserInfoInNavigation(?User $user): bool
    {
        return !is_null($user) && $user->isRegistered();
    }

    public function canSeeProfileDropdownInSideNavigation(?User $user): bool
    {
        return !is_null($user) && $user->isRegistered();
    }

    public function canSeeOwnProfilePhoto(?User $user): bool
    {
        return !is_null($user) && $user->hasProfilePhoto();
    }

    public function canSeeOwnProfileName(?User $user): bool
    {
        return !is_null($user)
            && (
                !is_null($user->getFirstName())
                || !is_null($user->getLastName())
            );
    }

    public function canSeeFooterOnFullPage(?User $user): bool
    {
        return is_null($user);
    }

    public function mustBeForcedToClaimUnregisteredUser(User $user): bool
    {
        return !$user->isRegistered();
    }

    public function canBeAskedToUseExtension(User $user): bool
    {
        return $user->isExtensionOnly();
    }

    public function canPresentLandingpageOnCustomDomain(User $user): bool
    {
        return $this->hasCapability($user, Capability::CustomDomain);
    }

    public function canEditOrganizationName(User $user): bool
    {
        return $this->organizationDomainService->userHasAccessRight(
            $user,
            AccessRight::EDIT_ORGANIZATION_NAME
        );
    }

    public function canEditCustomDomainSetting(User $user): bool
    {
        return $this->organizationDomainService->userHasAccessRight(
            $user,
            AccessRight::EDIT_CUSTOM_DOMAIN_SETTINGS
        );
    }

    public function canPresentOwnLogoOnLandingpage(User $user): bool
    {
        return $this->hasCapability($user, Capability::CustomLogoOnLandingpage);
    }

    public function canEditCustomLogoSetting(User $user): bool
    {
        return $this->organizationDomainService->userHasAccessRight(
            $user,
            AccessRight::EDIT_CUSTOM_LOGO_SETTINGS
        );
    }

    public function canPresentAdFreeLandingpage(User $user): bool
    {
        return $this->hasCapability($user, Capability::AdFreeLandingpages);
    }

    public function canInviteOrganizationMembers(User $user): bool
    {
        return $this->organizationDomainService->userHasAccessRight(
            $user,
            AccessRight::INVITE_ORGANIZATION_MEMBERS
        );
    }

    public function canSeeOrganizationGroupsAndMembers(User $user): bool
    {
        return $this->organizationDomainService->userHasAccessRight(
            $user,
            AccessRight::SEE_ORGANIZATION_GROUPS_AND_MEMBERS
        );
    }

    public function canMoveOrganizationMembersIntoGroups(User $user): bool
    {
        return $this->organizationDomainService->userHasAccessRight(
            $user,
            AccessRight::MOVE_ORGANIZATION_MEMBERS_INTO_GROUPS
        );
    }

    private function hasCapability(
        ?User      $user,
        Capability $capability
    ): bool {
        if (is_null($user)) {
            return false;
        }

        return $this
            ->membershipPlanService
            ->getSubscribedMembershipPlanForCurrentlyActiveOrganization($user)
            ->hasCapability($capability);
    }
}
