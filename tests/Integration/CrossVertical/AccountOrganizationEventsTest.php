<?php

declare(strict_types=1);

namespace App\Tests\Integration\CrossVertical;

use App\Account\Domain\Service\AccountDomainServiceInterface;
use App\Account\Facade\AccountFacadeInterface;
use App\Organization\Domain\Entity\Organization;
use App\Organization\Domain\Service\OrganizationDomainServiceInterface;
use App\Organization\Facade\SymfonyEvent\CurrentlyActiveOrganizationChangedSymfonyEvent;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class AccountOrganizationEventsTest extends KernelTestCase
{
    private AccountDomainServiceInterface $accountService;
    private AccountFacadeInterface $accountFacade;
    private OrganizationDomainServiceInterface $orgService;
    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        /** @var AccountDomainServiceInterface $accountService */
        $accountService       = $container->get(AccountDomainServiceInterface::class);
        $this->accountService = $accountService;

        /** @var AccountFacadeInterface $accountFacade */
        $accountFacade       = $container->get(AccountFacadeInterface::class);
        $this->accountFacade = $accountFacade;

        /** @var OrganizationDomainServiceInterface $orgService */
        $orgService       = $container->get(OrganizationDomainServiceInterface::class);
        $this->orgService = $orgService;

        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher       = $container->get(EventDispatcherInterface::class);
        $this->eventDispatcher = $eventDispatcher;
    }

    public function testAccountCreationTriggersOrganizationCreation(): void
    {
        $email = 'eventtest-' . uniqid() . '@example.com';

        // Register account - this dispatches AccountCoreCreatedSymfonyEvent
        $account   = $this->accountService->register($email, 'password123');
        $accountId = $account->getId();
        self::assertNotNull($accountId, 'Account ID should be set');

        // Organization should be automatically created
        $organizations = $this->orgService->getAllOrganizationsForUser($accountId);

        $this->assertNotEmpty($organizations);
        $this->assertCount(1, $organizations);
        $this->assertSame($accountId, $organizations[0]->getOwningUsersId());
    }

    public function testAccountCreationSetsActiveOrganization(): void
    {
        $email = 'activeorgtest-' . uniqid() . '@example.com';

        $account   = $this->accountService->register($email, 'password123');
        $accountId = $account->getId();
        self::assertNotNull($accountId, 'Account ID should be set');

        // Use facade to check active organization (avoids refresh issues with readonly properties)
        $activeOrgId = $this->accountFacade->getCurrentlyActiveOrganizationIdForAccountCore($accountId);

        $this->assertNotNull($activeOrgId);
    }

    public function testOrganizationCreatedForAccountHasDefaultGroups(): void
    {
        $email = 'groupstest-' . uniqid() . '@example.com';

        $account   = $this->accountService->register($email, 'password123');
        $accountId = $account->getId();
        self::assertNotNull($accountId, 'Account ID should be set');

        $activeOrgId = $this->accountFacade->getCurrentlyActiveOrganizationIdForAccountCore($accountId);
        self::assertNotNull($activeOrgId, 'Active organization ID should be set');

        $organization = $this->orgService->getOrganizationById($activeOrgId);
        self::assertNotNull($organization, 'Organization should exist');

        $groups = $this->orgService->getGroups($organization);

        $this->assertCount(2, $groups);
        $groupNames = array_map(fn (\App\Organization\Domain\Entity\Group $g): string => $g->getName(), $groups);
        $this->assertContains('Administrators', $groupNames);
        $this->assertContains('Team Members', $groupNames);
    }

    public function testCurrentlyActiveOrganizationChangedEventUpdatesAccount(): void
    {
        $email     = 'changedtest-' . uniqid() . '@example.com';
        $account   = $this->accountService->register($email, 'password123');
        $accountId = $account->getId();
        self::assertNotNull($accountId, 'Account ID should be set');

        // Create a second organization for the user
        $secondOrg = $this->orgService->createOrganization($accountId, 'Second Org');

        // Dispatch event to change active organization
        $this->eventDispatcher->dispatch(
            new CurrentlyActiveOrganizationChangedSymfonyEvent(
                $secondOrg->getId(),
                $accountId
            )
        );

        // Use facade to check active organization (avoids refresh issues with readonly properties)
        $activeOrgId = $this->accountFacade->getCurrentlyActiveOrganizationIdForAccountCore($accountId);

        $this->assertSame($secondOrg->getId(), $activeOrgId);
    }

    public function testSwitchOrganizationDispatchesEventAndUpdatesAccount(): void
    {
        $email     = 'switchtest-' . uniqid() . '@example.com';
        $account   = $this->accountService->register($email, 'password123');
        $accountId = $account->getId();
        self::assertNotNull($accountId, 'Account ID should be set');

        // Create second organization
        $secondOrg = $this->orgService->createOrganization($accountId, 'Switchable Org');

        // Get initial active org
        $initialOrgId = $this->accountFacade->getCurrentlyActiveOrganizationIdForAccountCore($accountId);
        $this->assertNotSame($secondOrg->getId(), $initialOrgId);

        // Switch to second org (this dispatches the event internally)
        $this->orgService->switchOrganization($accountId, $secondOrg);

        // Use facade to check active organization (avoids refresh issues with readonly properties)
        $activeOrgId = $this->accountFacade->getCurrentlyActiveOrganizationIdForAccountCore($accountId);

        $this->assertSame($secondOrg->getId(), $activeOrgId);
    }

    public function testUserCanSwitchOrganizationsAfterCreatingSecond(): void
    {
        $email     = 'canswitchtest-' . uniqid() . '@example.com';
        $account   = $this->accountService->register($email, 'password123');
        $accountId = $account->getId();
        self::assertNotNull($accountId, 'Account ID should be set');

        // Initially cannot switch (only one org)
        $this->assertFalse($this->orgService->userCanSwitchOrganizations($accountId));

        // Create second organization
        $this->orgService->createOrganization($accountId, 'Second Org');

        // Now can switch
        $this->assertTrue($this->orgService->userCanSwitchOrganizations($accountId));
    }

    public function testOrganizationsUserCanSwitchToIncludesAllOwned(): void
    {
        $email     = 'allownedtest-' . uniqid() . '@example.com';
        $account   = $this->accountService->register($email, 'password123');
        $accountId = $account->getId();
        self::assertNotNull($accountId, 'Account ID should be set');

        // Create additional organizations
        $org2 = $this->orgService->createOrganization($accountId, 'Org 2');
        $org3 = $this->orgService->createOrganization($accountId, 'Org 3');

        $switchable = $this->orgService->organizationsUserCanSwitchTo($accountId);

        $this->assertCount(3, $switchable); // Original + 2 created
        $orgIds = array_map(fn (Organization $o): string => $o->getId(), $switchable);
        $this->assertContains($org2->getId(), $orgIds);
        $this->assertContains($org3->getId(), $orgIds);
    }

    public function testCrossVerticalDataIntegrity(): void
    {
        $email = 'integritytest-' . uniqid() . '@example.com';

        // Create account
        $account   = $this->accountService->register($email, 'password123');
        $accountId = $account->getId();
        self::assertNotNull($accountId, 'Account ID should be set');

        // Get created organization via account's active org
        $activeOrgId = $account->getCurrentlyActiveOrganizationId();
        self::assertNotNull($activeOrgId, 'Active organization ID should be set');

        $organization = $this->orgService->getOrganizationById($activeOrgId);
        self::assertNotNull($organization, 'Organization should exist');

        // Verify cross-vertical reference integrity (via GUID, not FK)
        $this->assertSame($accountId, $organization->getOwningUsersId());

        // Verify account can be looked up from organization service perspective
        $allUserIds = $this->orgService->getAllUserIdsForOrganization($organization);
        $this->assertContains($accountId, $allUserIds);

        // Verify organization can be looked up from account perspective
        $orgs   = $this->orgService->getAllOrganizationsForUser($accountId);
        $orgIds = array_map(fn (Organization $o): string => $o->getId(), $orgs);
        $this->assertContains($activeOrgId, $orgIds);
    }

    public function testCurrentlyActiveOrganizationIsOwnAfterCreation(): void
    {
        $email     = 'owntest-' . uniqid() . '@example.com';
        $account   = $this->accountService->register($email, 'password123');
        $accountId = $account->getId();
        self::assertNotNull($accountId, 'Account ID should be set');

        $isOwn = $this->orgService->currentlyActiveOrganizationIsOwnOrganization($accountId);

        $this->assertTrue($isOwn);
    }
}
