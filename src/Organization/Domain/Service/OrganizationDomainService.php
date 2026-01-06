<?php

namespace App\Organization\Domain\Service;

use App\Account\Domain\Entity\User;
use App\Organization\Domain\Entity\Group;
use App\Organization\Domain\Entity\Invitation;
use App\Organization\Domain\Entity\Organization;
use App\Organization\Domain\Enum\AccessRight;
use App\Organization\Presentation\Service\OrganizationPresentationService;
use App\Shared\Domain\Enum\Iso639_1Code;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class OrganizationDomainService
{
    public function __construct(
        private TranslatorInterface             $translator,
        private EntityManagerInterface          $entityManager,
        private OrganizationPresentationService $organizationPresentationService,
        private AccountDomainService            $accountDomainService
    ) {
    }

    /** @return Organization[] */
    public function getAllOrganizationsForUser(
        User $user
    ): array {
        return array_merge(
            $user->getOwnedOrganizations()->toArray(),
            $user->getJoinedOrganizations()->toArray()
        );
    }

    public function userJoinedOrganizations(
        User $user
    ): bool {
        return sizeof($user->getJoinedOrganizations()->toArray()) > 0;
    }

    /**
     * @throws Exception
     */
    public function userJoinedOrganization(
        User         $user,
        Organization $organization
    ): bool {
        foreach ($user->getJoinedOrganizations() as $joinedOrganization) {
            if ($joinedOrganization->getId() === $organization->getId()) {
                return true;
            }
        }

        return false;
    }

    public function userCanCreateOrManageOrganization(
        User $user
    ): bool {
        if (!$this->userJoinedOrganizations($user)) {
            return true;
        }

        return false;
    }

    public function getCurrentlyActiveOrganizationOfUser(
        User $user
    ): Organization {
        return $user->getCurrentlyActiveOrganization();
    }

    /**
     * @throws Exception|ORMException
     */
    public function createOrganization(
        User $owningUser
    ): Organization {
        $organization = new Organization($owningUser);

        $adminGroup = new Group(
            $organization,
            'Administrators',
            [AccessRight::FULL_ACCESS],
            false
        );

        $this->entityManager->persist($adminGroup);

        $teamMemberGroup = new Group(
            $organization,
            'Team Members',
            [AccessRight::SEE_ORGANIZATION_GROUPS_AND_MEMBERS],
            true
        );

        $this->entityManager->persist($teamMemberGroup);

        $this->entityManager->persist($organization);
        $this->entityManager->flush();
        $this->entityManager->refresh($owningUser);

        return $organization;
    }

    public function emailCanBeInvitedToOrganization(
        string       $email,
        Organization $organization
    ): bool {
        /** @var User|null $user */
        $user = $this
            ->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => trim(mb_strtolower($email))]);

        if (is_null($user)) {
            return true;
        }

        foreach ($user->getJoinedOrganizations() as $joinedOrganization) {
            if ($joinedOrganization->getId() === $organization->getId()) {
                return false;
            }
        }

        foreach ($user->getOwnedOrganizations() as $ownedOrganization) {
            if ($ownedOrganization->getId() === $organization->getId()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws Exception|TransportExceptionInterface
     */
    public function inviteEmailToOrganization(
        string       $email,
        Organization $organization
    ): ?Invitation {
        $email = trim(mb_strtolower($email));
        if (!$this->emailCanBeInvitedToOrganization($email, $organization)) {
            return null;
        }

        /** @var ObjectRepository<Invitation> $repo */
        $repo = $this->entityManager->getRepository(Invitation::class);

        /** @var Invitation|null $invitation */
        $invitation = $repo->findOneBy(['email' => $email]);

        if (is_null($invitation)) {
            $invitation = new Invitation($organization, $email);
            $this->entityManager->persist($invitation);
            $this->entityManager->flush();
        } else {
            if ($invitation->getOrganization()->getId() !== $organization->getId()) {
                return null;
            }
        }

        $this->organizationPresentationService->sendInvitationMail($invitation);

        return $invitation;
    }

    /**
     * @throws Exception|ORMException
     */
    public function acceptInvitation(
        Invitation $invitation,
        ?User      $user
    ): ?User {
        if (!is_null($user)) {
            foreach ($user->getJoinedOrganizations() as $joinedOrganization) {
                if ($joinedOrganization->getId() === $invitation->getOrganization()->getId()) {
                    return $user;
                }
            }

            if ($user->isUnregistered()) {
                $this->accountDomainService->handleUnregisteredUserClaimsEmail(
                    $user,
                    $invitation->getEmail(),
                    null
                );
                $user->setIsVerified(true);
            }
        } else {
            $user = $this->accountDomainService->createRegisteredUser(
                $invitation->getEmail(),
                null,
                true
            );
        }

        $defaultGroup = $this->getDefaultGroupForNewMembers(
            $invitation->getOrganization()
        );

        $user->addJoinedOrganization($invitation->getOrganization());
        $user->setCurrentlyActiveOrganization($invitation->getOrganization());
        $invitation->getOrganization()->addJoinedUser($user);
        $defaultGroup->addMember($user);

        $this->entityManager->persist($user);
        $this->entityManager->persist($invitation->getOrganization());
        $this->entityManager->persist($defaultGroup);
        $this->entityManager->flush();

        $this->entityManager->refresh($invitation->getOrganization());

        $this->entityManager->remove($invitation);
        unset($invitation);
        $this->entityManager->flush();

        return $user;
    }

    public function getOrganizationName(
        Organization  $organization,
        ?Iso639_1Code $iso639_1Code,
    ): string {
        if (is_null($iso639_1Code)) {
            $iso639_1Code = Iso639_1Code::En;
        }
        if (is_null($organization->getName())) {
            return $this->translator->trans(
                'default_organization_name',
                [],
                'etfs.organization',
                $iso639_1Code->value,
            );
        } else {
            return $organization->getName();
        }
    }

    public function hasPendingInvitations(
        Organization $organization
    ): bool {
        return sizeof($this->getPendingInvitations($organization)) > 0;
    }

    /** @return Invitation[] */
    public function getPendingInvitations(
        Organization $organization
    ): array {
        /** @var ObjectRepository<Invitation> $repo */
        $repo = $this->entityManager->getRepository(Invitation::class);

        return $repo->findBy(
            ['organization' => $organization],
            ['createdAt' => Criteria::DESC]
        );
    }

    /** @return User[] */
    public function getAllUsersOfOrganization(
        Organization $organization
    ): array {
        return array_merge(
            [$organization->getOwningUser()],
            $organization->getJoinedUsers()->toArray()
        );
    }

    public function getGroupName(
        Group        $group,
        Iso639_1Code $iso639_1Code,
    ): string {
        return $this->translator->trans(
            "group.name.{$group->getName()}",
            [],
            'etfs.organization',
            $iso639_1Code->value,
        );
    }

    /** @return Group[] */
    public function getGroups(
        Organization $organization
    ): array {
        /** @var ObjectRepository<Group> $repo */
        $repo = $this->entityManager->getRepository(Group::class);

        return $repo->findBy(
            ['organization' => $organization],
            ['createdAt' => Criteria::DESC]
        );
    }

    /** @return Group[] */
    public function getGroupsOfUserForCurrentlyActiveOrganization(
        User $user
    ): array {
        $organization = $this->getCurrentlyActiveOrganizationOfUser($user);

        /** @var ObjectRepository<Group> $repo */
        $repo = $this->entityManager->getRepository(Group::class);

        /** @var Group[] $allGroups */
        $allGroups = $repo->findBy(
            ['organization' => $organization],
            ['createdAt' => Criteria::DESC]
        );

        /** @var Group[] $foundGroups */
        $foundGroups = [];
        foreach ($allGroups as $group) {
            foreach ($group->getMembers() as $member) {
                if ($member->getId() === $user->getId()) {
                    $foundGroups[] = $group;
                }
            }
        }

        return $foundGroups;
    }

    /**
     * @throws Exception
     */
    public function getDefaultGroupForNewMembers(
        Organization $organization
    ): Group {
        /** @var ObjectRepository<Group> $repo */
        $repo = $this->entityManager->getRepository(Group::class);

        /** @var Group|null $group */
        $group = $repo->findOneBy(
            [
                'organization'           => $organization,
                'isDefaultForNewMembers' => true
            ]
        );

        if (is_null($group)) {
            throw new Exception(
                "Organization '{$organization->getId()}' does not have default group for new members."
            );
        }

        return $group;
    }

    /** @return User[] */
    public function getGroupMembers(
        Group $group
    ): array {
        return $group->getMembers();
    }

    public function moveUserToAdministratorsGroup(
        User         $user,
        Organization $organization
    ): void {
        $groups = $this->getGroups(
            $organization
        );

        foreach ($groups as $group) {
            if ($group->isAdministratorsGroup()) {
                $group->addMember($user);
            } else {
                $group->removeMember($user);
            }
            $this->entityManager->persist($group);
        }

        $this->entityManager->flush();
    }

    public function moveUserToTeamMembersGroup(
        User         $user,
        Organization $organization
    ): void {
        $groups = $this->getGroups(
            $organization
        );

        foreach ($groups as $group) {
            if ($group->isTeamMembersGroup()) {
                $group->addMember($user);
            } else {
                $group->removeMember($user);
            }
            $this->entityManager->persist($group);
        }

        $this->entityManager->flush();
    }

    public function userHasAccessRight(
        User        $user,
        AccessRight $accessRight
    ): bool {
        if ($this->getCurrentlyActiveOrganizationOfUser($user)->getOwningUser()->getId()
            === $user->getId()
        ) {
            return true;
        }

        foreach ($this->getGroupsOfUserForCurrentlyActiveOrganization($user) as $group) {
            foreach ($group->getAccessRights() as $groupAccessRight) {
                if ($groupAccessRight    === AccessRight::FULL_ACCESS
                    || $groupAccessRight === $accessRight
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    public function currentlyActiveOrganizationIsOwnOrganization(
        User $user
    ): bool {
        return $user->getCurrentlyActiveOrganization()->getOwningUser()->getId()
            === $user->getId();
    }

    public function userCanSwitchOrganizations(
        User $user
    ): bool {
        return $user->getJoinedOrganizations()->count()
            + $user->getOwnedOrganizations()->count()
            > 1;
    }

    /** @return Organization[] */
    public function organizationsUserCanSwitchTo(
        User $user
    ): array {
        return array_merge(
            $user->getJoinedOrganizations()->toArray(),
            $user->getOwnedOrganizations()->toArray()
        );
    }

    public function switchOrganization(
        User         $user,
        Organization $organization
    ): void {
        foreach ($this->organizationsUserCanSwitchTo($user) as $switchableOrganization) {
            if ($switchableOrganization->getId() === $organization->getId()) {
                $user->setCurrentlyActiveOrganization($organization);
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return;
            }
        }
    }
}
