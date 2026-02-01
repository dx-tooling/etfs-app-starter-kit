<?php

declare(strict_types=1);

namespace App\Tests\Integration\Organization;

use App\Account\Facade\AccountFacadeInterface;
use App\Account\Facade\Dto\UserRegistrationDto;
use App\Organization\Domain\Entity\Group;
use App\Organization\Domain\Enum\AccessRight;
use App\Organization\Domain\Service\OrganizationDomainServiceInterface;
use App\Shared\Facade\ValueObject\EmailAddress;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class OrganizationDomainServiceTest extends KernelTestCase
{
    private OrganizationDomainServiceInterface $service;
    private AccountFacadeInterface $accountFacade;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        /** @var OrganizationDomainServiceInterface $service */
        $service       = $container->get(OrganizationDomainServiceInterface::class);
        $this->service = $service;

        /** @var AccountFacadeInterface $accountFacade */
        $accountFacade       = $container->get(AccountFacadeInterface::class);
        $this->accountFacade = $accountFacade;
    }

    private function createTestUser(): string
    {
        $email  = 'orgtest-' . uniqid() . '@example.com';
        $result = $this->accountFacade->register(new UserRegistrationDto(
            EmailAddress::fromString($email),
            'password123',
            false
        ));

        self::assertNotNull($result->userId, 'User registration should return a user ID');

        return $result->userId;
    }

    /**
     * @param list<Group> $groups
     */
    private function findAdminGroup(array $groups): Group
    {
        foreach ($groups as $group) {
            if ($group->isAdministratorsGroup()) {
                return $group;
            }
        }
        self::fail('Admin group not found');
    }

    public function testCreateOrganizationCreatesWithOwningUser(): void
    {
        $userId = $this->createTestUser();

        $organization = $this->service->createOrganization($userId, 'Test Org');

        $this->assertSame($userId, $organization->getOwningUsersId());
        $this->assertSame('Test Org', $organization->getName());
    }

    public function testCreateOrganizationCreatesDefaultGroups(): void
    {
        $userId = $this->createTestUser();

        $organization = $this->service->createOrganization($userId);
        $groups       = $this->service->getGroups($organization);

        $this->assertCount(2, $groups);

        $groupNames = array_map(fn (Group $g): string => $g->getName(), $groups);
        $this->assertContains('Administrators', $groupNames);
        $this->assertContains('Team Members', $groupNames);
    }

    public function testCreateOrganizationAdministratorsGroupHasFullAccess(): void
    {
        $userId = $this->createTestUser();

        $organization = $this->service->createOrganization($userId);
        $groups       = $this->service->getGroups($organization);
        $adminGroup   = $this->findAdminGroup($groups);

        $this->assertContains(AccessRight::FULL_ACCESS, $adminGroup->getAccessRights());
    }

    public function testCreateOrganizationTeamMembersIsDefaultForNewMembers(): void
    {
        $userId = $this->createTestUser();

        $organization = $this->service->createOrganization($userId);
        $defaultGroup = $this->service->getDefaultGroupForNewMembers($organization);

        $this->assertTrue($defaultGroup->isTeamMembersGroup());
    }

    public function testRenameOrganization(): void
    {
        $userId       = $this->createTestUser();
        $organization = $this->service->createOrganization($userId, 'Original Name');

        $this->service->renameOrganization($organization, 'New Name');

        $this->assertSame('New Name', $organization->getName());
    }

    public function testRenameOrganizationToNull(): void
    {
        $userId       = $this->createTestUser();
        $organization = $this->service->createOrganization($userId, 'Has Name');

        $this->service->renameOrganization($organization, null);

        $this->assertNull($organization->getName());
    }

    public function testGetOrganizationById(): void
    {
        $userId  = $this->createTestUser();
        $created = $this->service->createOrganization($userId, 'Find Me');

        $found = $this->service->getOrganizationById($created->getId());

        $this->assertNotNull($found);
        $this->assertSame($created->getId(), $found->getId());
    }

    public function testGetOrganizationByIdReturnsNullForUnknown(): void
    {
        $found = $this->service->getOrganizationById('nonexistent-org-id');

        $this->assertNull($found);
    }

    public function testGetAllOrganizationsForUserIncludesOwned(): void
    {
        $userId = $this->createTestUser();
        // Note: Account creation automatically creates an organization

        $organizations = $this->service->getAllOrganizationsForUser($userId);

        $this->assertNotEmpty($organizations);
        $ownerIds = array_map(fn (\App\Organization\Domain\Entity\Organization $o): string => $o->getOwningUsersId(), $organizations);
        $this->assertContains($userId, $ownerIds);
    }

    public function testUserCanCreateOrganizationReturnsTrueWhenOnlyOwnsOrg(): void
    {
        $userId = $this->createTestUser();

        // User owns their own org but hasn't JOINED any org - can still manage
        // userHasJoinedOrganizations checks organization_members table (joined orgs, not owned)
        $this->assertTrue($this->service->userCanCreateOrManageOrganization($userId));
    }

    public function testCurrentlyActiveOrganizationIsOwnOrganization(): void
    {
        $userId = $this->createTestUser();

        $isOwn = $this->service->currentlyActiveOrganizationIsOwnOrganization($userId);

        $this->assertTrue($isOwn);
    }

    public function testAddUserToGroup(): void
    {
        $ownerUserId  = $this->createTestUser();
        $memberUserId = $this->createTestUser();

        $ownerOrgId = $this->accountFacade->getCurrentlyActiveOrganizationIdForAccountCore($ownerUserId);
        self::assertNotNull($ownerOrgId, 'Owner should have an active organization');

        $organization = $this->service->getOrganizationById($ownerOrgId);
        self::assertNotNull($organization, 'Organization should exist');

        $groups     = $this->service->getGroups($organization);
        $adminGroup = $this->findAdminGroup($groups);

        $this->service->addUserToGroup($memberUserId, $adminGroup);

        $memberIds = $this->service->getGroupMemberIds($adminGroup);
        $this->assertContains($memberUserId, $memberIds);
    }

    public function testRemoveUserFromGroup(): void
    {
        $ownerUserId  = $this->createTestUser();
        $memberUserId = $this->createTestUser();

        $ownerOrgId = $this->accountFacade->getCurrentlyActiveOrganizationIdForAccountCore($ownerUserId);
        self::assertNotNull($ownerOrgId, 'Owner should have an active organization');

        $organization = $this->service->getOrganizationById($ownerOrgId);
        self::assertNotNull($organization, 'Organization should exist');

        $groups     = $this->service->getGroups($organization);
        $adminGroup = $this->findAdminGroup($groups);

        $this->service->addUserToGroup($memberUserId, $adminGroup);
        $this->service->removeUserFromGroup($memberUserId, $adminGroup);

        $memberIds = $this->service->getGroupMemberIds($adminGroup);
        $this->assertNotContains($memberUserId, $memberIds);
    }

    public function testUserHasAccessRightReturnsTrueForOwner(): void
    {
        $userId = $this->createTestUser();

        // Owner has all access rights
        $hasRight = $this->service->userHasAccessRight($userId, AccessRight::FULL_ACCESS);

        $this->assertTrue($hasRight);
    }

    public function testUserCanSwitchOrganizationsReturnsFalseWithSingleOrg(): void
    {
        $userId = $this->createTestUser();

        $canSwitch = $this->service->userCanSwitchOrganizations($userId);

        $this->assertFalse($canSwitch);
    }

    public function testGetAllUserIdsForOrganizationIncludesOwner(): void
    {
        $userId = $this->createTestUser();
        $orgId  = $this->accountFacade->getCurrentlyActiveOrganizationIdForAccountCore($userId);
        self::assertNotNull($orgId, 'User should have an active organization');

        $organization = $this->service->getOrganizationById($orgId);
        self::assertNotNull($organization, 'Organization should exist');

        $userIds = $this->service->getAllUserIdsForOrganization($organization);

        $this->assertContains($userId, $userIds);
    }
}
