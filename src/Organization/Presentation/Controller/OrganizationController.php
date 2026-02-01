<?php

declare(strict_types=1);

namespace App\Organization\Presentation\Controller;

use App\Account\Facade\AccountFacadeInterface;
use App\Account\Facade\Dto\AccountInfoDto;
use App\Organization\Domain\Entity\Invitation;
use App\Organization\Domain\Service\OrganizationDomainServiceInterface;
use App\Organization\Facade\SymfonyEvent\CurrentlyActiveOrganizationChangedSymfonyEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Throwable;

final class OrganizationController extends AbstractController
{
    public function __construct(
        private readonly OrganizationDomainServiceInterface $organizationDomainService,
        private readonly EventDispatcherInterface           $eventDispatcher,
        private readonly EntityManagerInterface             $entityManager,
        private readonly AccountFacadeInterface             $accountFacade,
        private readonly Security                           $security
    ) {
    }

    private function getAccountInfo(UserInterface $user): AccountInfoDto
    {
        $accountInfo = $this->accountFacade->getLoggedInAccountCoreInfo($user);

        if ($accountInfo === null) {
            throw new RuntimeException('Account not found for authenticated user');
        }

        return $accountInfo;
    }

    #[Route(
        path: '/organization',
        name: 'organization.presentation.dashboard',
        methods: [Request::METHOD_GET]
    )]
    public function dashboardAction(): Response
    {
        $user = $this->getUser();

        if ($user === null) {
            return $this->redirectToRoute('account.presentation.sign_in');
        }

        $accountInfo                   = $this->getAccountInfo($user);
        $userId                        = $accountInfo->id;
        $organizationName              = null;
        $currentOrganization           = null;
        $currentlyActiveOrganizationId = $accountInfo->currentlyActiveOrganizationId;

        if ($currentlyActiveOrganizationId !== null) {
            $currentOrganization = $this->organizationDomainService->getOrganizationById($currentlyActiveOrganizationId);
            if ($currentOrganization !== null) {
                $organizationName = $this->organizationDomainService->getOrganizationName($currentOrganization, null);
            }
        }

        // Get all organizations for switching
        $allOrganizations = $this->organizationDomainService->getAllOrganizationsForUser($userId);

        // Check if user can rename/invite for current organization (owner of active org)
        $canRenameCurrentOrganization   = false;
        $canInviteToCurrentOrganization = false;
        $currentOrganizationRawName     = null;
        $pendingInvitations             = [];
        $members                        = [];

        if ($currentOrganization !== null) {
            $isOwner                        = $currentOrganization->getOwningUsersId() === $userId;
            $canRenameCurrentOrganization   = $isOwner;
            $canInviteToCurrentOrganization = $isOwner;
            $currentOrganizationRawName     = $currentOrganization->getName();

            // Get pending invitations if owner
            if ($isOwner) {
                $invitations = $this->organizationDomainService->getPendingInvitations($currentOrganization);
                foreach ($invitations as $invitation) {
                    $pendingInvitations[] = [
                        'id'        => $invitation->getId(),
                        'email'     => $invitation->getEmail(),
                        'createdAt' => $invitation->getCreatedAt(),
                    ];
                }
            }

            // Get members of the organization
            $memberIds   = $this->organizationDomainService->getAllUserIdsForOrganization($currentOrganization);
            $ownerUserId = $currentOrganization->getOwningUsersId();

            // Include owner in the list if not already
            if (!in_array($ownerUserId, $memberIds, true)) {
                $memberIds[] = $ownerUserId;
            }

            // Get all groups for this organization
            $orgGroups = $this->organizationDomainService->getGroups($currentOrganization);

            // Build a map of userId -> groupIds for quick lookup
            /** @var array<string, list<string>> $userGroupMap */
            $userGroupMap = [];
            foreach ($orgGroups as $group) {
                $groupMemberIds = $this->organizationDomainService->getGroupMemberIds($group);
                foreach ($groupMemberIds as $memberId) {
                    if (!array_key_exists($memberId, $userGroupMap)) {
                        $userGroupMap[$memberId] = [];
                    }
                    $userGroupMap[$memberId][] = $group->getId();
                }
            }

            $memberInfos = $this->accountFacade->getAccountCoreInfoByIds($memberIds);
            foreach ($memberInfos as $memberInfo) {
                $members[] = [
                    'id'            => $memberInfo->id,
                    'displayName'   => $memberInfo->getDisplayName(),
                    'email'         => $memberInfo->email,
                    'isOwner'       => $memberInfo->id === $ownerUserId,
                    'isCurrentUser' => $memberInfo->id === $userId,
                    'joinedAt'      => $memberInfo->createdAt,
                    'groupIds'      => $userGroupMap[$memberInfo->id] ?? [],
                ];
            }

            // Sort: owner first, then by display name
            usort($members, function (array $a, array $b): int {
                if ($a['isOwner'] !== $b['isOwner']) {
                    return $a['isOwner'] ? -1 : 1;
                }

                return strcasecmp($a['displayName'], $b['displayName']);
            });
        }

        // Build organization list with names
        $organizations = [];
        foreach ($allOrganizations as $org) {
            $organizations[] = [
                'id'       => $org->getId(),
                'name'     => $this->organizationDomainService->getOrganizationName($org, null),
                'isOwned'  => $org->getOwningUsersId()                       === $userId,
                'isActive' => $currentOrganization !== null && $org->getId() === $currentOrganization->getId(),
            ];
        }

        // Build groups list
        $groups = [];
        if ($currentOrganization !== null) {
            $orgGroups = $this->organizationDomainService->getGroups($currentOrganization);
            foreach ($orgGroups as $group) {
                $groups[] = [
                    'id'        => $group->getId(),
                    'name'      => $group->getName(),
                    'isDefault' => $group->isDefaultForNewMembers(),
                ];
            }
        }

        return $this->render('@organization.presentation/organization_dashboard.html.twig', [
            'organizationName'               => $organizationName,
            'organizations'                  => $organizations,
            'currentOrganizationId'          => $currentlyActiveOrganizationId,
            'canRenameCurrentOrganization'   => $canRenameCurrentOrganization,
            'canInviteToCurrentOrganization' => $canInviteToCurrentOrganization,
            'currentOrganizationRawName'     => $currentOrganizationRawName,
            'pendingInvitations'             => $pendingInvitations,
            'members'                        => $members,
            'groups'                         => $groups,
            'isOrganizationOwner'            => $currentOrganization !== null && $currentOrganization->getOwningUsersId() === $userId,
        ]);
    }

    #[Route(
        path: '/organization/create',
        name: 'organization.presentation.create',
        methods: [Request::METHOD_POST]
    )]
    public function createAction(Request $request): Response
    {
        $user = $this->getUser();

        if ($user === null) {
            return $this->redirectToRoute('account.presentation.sign_in');
        }

        $accountInfo = $this->getAccountInfo($user);
        $userId      = $accountInfo->id;

        try {
            $name = $request->request->get('name');
            $name = is_string($name) && trim($name) !== '' ? trim($name) : null;

            if ($name === null) {
                $this->addFlash('error', 'Please provide a name for the organization.');

                return $this->redirectToRoute('organization.presentation.dashboard');
            }

            $organization = $this->organizationDomainService->createOrganization($userId, $name);

            // Switch to the new organization
            $this->eventDispatcher->dispatch(
                new CurrentlyActiveOrganizationChangedSymfonyEvent(
                    $organization->getId(),
                    $userId
                )
            );

            $displayName = $this->organizationDomainService->getOrganizationName($organization, null);
            $this->addFlash('success', "Organization \"$displayName\" created successfully.");
        } catch (Throwable $e) {
            $this->addFlash('error', 'Failed to create organization: ' . $e->getMessage());
        }

        return $this->redirectToRoute('organization.presentation.dashboard');
    }

    #[Route(
        path: '/organization/rename',
        name: 'organization.presentation.rename',
        methods: [Request::METHOD_POST]
    )]
    public function renameAction(Request $request): Response
    {
        $user = $this->getUser();

        if ($user === null) {
            return $this->redirectToRoute('account.presentation.sign_in');
        }

        $accountInfo    = $this->getAccountInfo($user);
        $userId         = $accountInfo->id;
        $organizationId = $accountInfo->currentlyActiveOrganizationId;

        if ($organizationId === null) {
            $this->addFlash('error', 'No active organization to rename.');

            return $this->redirectToRoute('organization.presentation.dashboard');
        }

        try {
            $organization = $this->organizationDomainService->getOrganizationById($organizationId);

            if ($organization === null) {
                $this->addFlash('error', 'Organization not found.');

                return $this->redirectToRoute('organization.presentation.dashboard');
            }

            // Only owner can rename
            if ($organization->getOwningUsersId() !== $userId) {
                $this->addFlash('error', 'Only the organization owner can rename it.');

                return $this->redirectToRoute('organization.presentation.dashboard');
            }

            $name = $request->request->get('name');
            $name = is_string($name) && trim($name) !== '' ? trim($name) : null;

            $this->organizationDomainService->renameOrganization($organization, $name);

            $displayName = $this->organizationDomainService->getOrganizationName($organization, null);
            $this->addFlash('success', "Organization renamed to \"$displayName\".");
        } catch (Throwable $e) {
            $this->addFlash('error', 'Failed to rename organization: ' . $e->getMessage());
        }

        return $this->redirectToRoute('organization.presentation.dashboard');
    }

    #[Route(
        path: '/organization/switch/{organizationId}',
        name: 'organization.presentation.switch',
        methods: [Request::METHOD_POST]
    )]
    public function switchAction(Request $request, string $organizationId): Response
    {
        $user = $this->getUser();

        if ($user === null) {
            return $this->redirectToRoute('account.presentation.sign_in');
        }

        $accountInfo = $this->getAccountInfo($user);
        $userId      = $accountInfo->id;

        try {
            $organization = $this->organizationDomainService->getOrganizationById($organizationId);

            if ($organization === null) {
                $this->addFlash('error', 'Organization not found.');

                return $this->redirectToRoute('organization.presentation.dashboard');
            }

            $this->organizationDomainService->switchOrganization($userId, $organization);

            $organizationName = $this->organizationDomainService->getOrganizationName($organization, null);
            $this->addFlash('success', "Switched to \"$organizationName\".");
        } catch (Throwable $e) {
            $this->addFlash('error', 'Failed to switch organization: ' . $e->getMessage());
        }

        return $this->redirectToRoute('organization.presentation.dashboard');
    }

    #[Route(
        path: '/organization/invite',
        name: 'organization.presentation.invite',
        methods: [Request::METHOD_POST]
    )]
    public function inviteAction(Request $request): Response
    {
        $user = $this->getUser();

        if ($user === null) {
            return $this->redirectToRoute('account.presentation.sign_in');
        }

        $accountInfo    = $this->getAccountInfo($user);
        $userId         = $accountInfo->id;
        $organizationId = $accountInfo->currentlyActiveOrganizationId;

        if ($organizationId === null) {
            $this->addFlash('error', 'No active organization to invite to.');

            return $this->redirectToRoute('organization.presentation.dashboard');
        }

        try {
            $organization = $this->organizationDomainService->getOrganizationById($organizationId);

            if ($organization === null) {
                $this->addFlash('error', 'Organization not found.');

                return $this->redirectToRoute('organization.presentation.dashboard');
            }

            // Only owner can invite
            if ($organization->getOwningUsersId() !== $userId) {
                $this->addFlash('error', 'Only the organization owner can invite members.');

                return $this->redirectToRoute('organization.presentation.dashboard');
            }

            $email = $request->request->get('email');
            $email = is_string($email) ? trim(mb_strtolower($email)) : '';

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', 'Please provide a valid email address.');

                return $this->redirectToRoute('organization.presentation.dashboard');
            }

            // Check if can be invited
            if (!$this->organizationDomainService->emailCanBeInvitedToOrganization($email, $organization)) {
                $this->addFlash('error', 'This email is already a member of this organization or is the owner.');

                return $this->redirectToRoute('organization.presentation.dashboard');
            }

            $invitation = $this->organizationDomainService->inviteEmailToOrganization($email, $organization);

            if ($invitation === null) {
                $this->addFlash('error', 'Could not send invitation.');
            } else {
                $this->addFlash('success', "Invitation sent to $email.");
            }
        } catch (Throwable $e) {
            $this->addFlash('error', 'Failed to send invitation: ' . $e->getMessage());
        }

        return $this->redirectToRoute('organization.presentation.dashboard');
    }

    #[Route(
        path: '/organization/invitation/{invitationId}/resend',
        name: 'organization.presentation.resend_invitation',
        methods: [Request::METHOD_POST]
    )]
    public function resendInvitationAction(Request $request, string $invitationId): Response
    {
        $user = $this->getUser();

        if ($user === null) {
            return $this->redirectToRoute('account.presentation.sign_in');
        }

        $accountInfo = $this->getAccountInfo($user);
        $userId      = $accountInfo->id;

        try {
            $invitation = $this->entityManager->getRepository(Invitation::class)->find($invitationId);

            if ($invitation === null) {
                $this->addFlash('error', 'Invitation not found.');

                return $this->redirectToRoute('organization.presentation.dashboard');
            }

            $organization = $invitation->getOrganization();

            // Only owner can resend invitations
            if ($organization->getOwningUsersId() !== $userId) {
                $this->addFlash('error', 'Only the organization owner can resend invitations.');

                return $this->redirectToRoute('organization.presentation.dashboard');
            }

            $this->organizationDomainService->resendInvitation($invitation);

            $this->addFlash('success', "Invitation resent to {$invitation->getEmail()}.");
        } catch (Throwable $e) {
            $this->addFlash('error', 'Failed to resend invitation: ' . $e->getMessage());
        }

        return $this->redirectToRoute('organization.presentation.dashboard');
    }

    #[Route(
        path: '/organization/invitation/{invitationId}',
        name: 'organization.presentation.accept_invitation',
        methods: [Request::METHOD_GET, Request::METHOD_POST]
    )]
    public function acceptInvitationAction(Request $request, string $invitationId): Response
    {
        // Find the invitation
        $invitation = $this->entityManager->getRepository(Invitation::class)->find($invitationId);

        if ($invitation === null) {
            $this->addFlash('error', 'Invitation not found or has already been used.');

            return $this->redirectToRoute('content.presentation.homepage');
        }

        $organization     = $invitation->getOrganization();
        $ownerName        = $this->accountFacade->getAccountCoreEmailById($organization->getOwningUsersId()) ?? 'Someone';
        $organizationName = $this->organizationDomainService->getOrganizationName($organization, null);

        // GET request - show the acceptance page
        if ($request->isMethod(Request::METHOD_GET)) {
            return $this->render('@organization.presentation/accept_invitation.html.twig', [
                'invitationId'     => $invitationId,
                'ownerName'        => $ownerName,
                'organizationName' => $organizationName,
            ]);
        }

        // POST request - accept the invitation
        try {
            $currentUser = $this->getUser();
            $userId      = $currentUser !== null
                ? $this->getAccountInfo($currentUser)->id
                : null;

            $newUserId = $this->organizationDomainService->acceptInvitation($invitation, $userId);

            if ($newUserId === null) {
                $this->addFlash('error', 'Failed to accept invitation.');

                return $this->redirectToRoute('content.presentation.homepage');
            }

            // If user wasn't logged in and we created a new one, log them in
            if ($currentUser === null) {
                $newUser = $this->accountFacade->getAccountCoreForLogin($newUserId);
                if ($newUser !== null) {
                    $this->security->login($newUser, 'form_login', 'main');
                }
            }

            $this->addFlash('success', "You've successfully joined \"$organizationName\".");

            return $this->redirectToRoute('organization.presentation.dashboard');
        } catch (Throwable $e) {
            $this->addFlash('error', 'Failed to accept invitation: ' . $e->getMessage());

            return $this->redirectToRoute('content.presentation.homepage');
        }
    }

    #[Route(
        path: '/organization/group/{groupId}/add-member',
        name: 'organization.presentation.add_member_to_group',
        methods: [Request::METHOD_POST]
    )]
    public function addMemberToGroupAction(Request $request, string $groupId): Response
    {
        $user = $this->getUser();

        if ($user === null) {
            return $this->redirectToRoute('account.presentation.sign_in');
        }

        $accountInfo    = $this->getAccountInfo($user);
        $userId         = $accountInfo->id;
        $organizationId = $accountInfo->currentlyActiveOrganizationId;

        if ($organizationId === null) {
            $this->addFlash('error', 'No active organization.');

            return $this->redirectToRoute('organization.presentation.dashboard');
        }

        try {
            $organization = $this->organizationDomainService->getOrganizationById($organizationId);

            if ($organization === null) {
                $this->addFlash('error', 'Organization not found.');

                return $this->redirectToRoute('organization.presentation.dashboard');
            }

            // Only owner can manage groups
            if ($organization->getOwningUsersId() !== $userId) {
                $this->addFlash('error', 'Only the organization owner can manage group membership.');

                return $this->redirectToRoute('organization.presentation.dashboard');
            }

            $group = $this->organizationDomainService->getGroupById($groupId);

            if ($group === null || $group->getOrganization()->getId() !== $organizationId) {
                $this->addFlash('error', 'Group not found.');

                return $this->redirectToRoute('organization.presentation.dashboard');
            }

            $memberId = $request->request->get('member_id');

            if (!is_string($memberId) || $memberId === '') {
                $this->addFlash('error', 'Invalid member ID.');

                return $this->redirectToRoute('organization.presentation.dashboard');
            }

            $this->organizationDomainService->addUserToGroup($memberId, $group);
            $this->addFlash('success', "Member added to {$group->getName()}.");
        } catch (Throwable $e) {
            $this->addFlash('error', 'Failed to add member to group: ' . $e->getMessage());
        }

        return $this->redirectToRoute('organization.presentation.dashboard');
    }

    #[Route(
        path: '/organization/group/{groupId}/remove-member',
        name: 'organization.presentation.remove_member_from_group',
        methods: [Request::METHOD_POST]
    )]
    public function removeMemberFromGroupAction(Request $request, string $groupId): Response
    {
        $user = $this->getUser();

        if ($user === null) {
            return $this->redirectToRoute('account.presentation.sign_in');
        }

        $accountInfo    = $this->getAccountInfo($user);
        $userId         = $accountInfo->id;
        $organizationId = $accountInfo->currentlyActiveOrganizationId;

        if ($organizationId === null) {
            $this->addFlash('error', 'No active organization.');

            return $this->redirectToRoute('organization.presentation.dashboard');
        }

        try {
            $organization = $this->organizationDomainService->getOrganizationById($organizationId);

            if ($organization === null) {
                $this->addFlash('error', 'Organization not found.');

                return $this->redirectToRoute('organization.presentation.dashboard');
            }

            // Only owner can manage groups
            if ($organization->getOwningUsersId() !== $userId) {
                $this->addFlash('error', 'Only the organization owner can manage group membership.');

                return $this->redirectToRoute('organization.presentation.dashboard');
            }

            $group = $this->organizationDomainService->getGroupById($groupId);

            if ($group === null || $group->getOrganization()->getId() !== $organizationId) {
                $this->addFlash('error', 'Group not found.');

                return $this->redirectToRoute('organization.presentation.dashboard');
            }

            $memberId = $request->request->get('member_id');

            if (!is_string($memberId) || $memberId === '') {
                $this->addFlash('error', 'Invalid member ID.');

                return $this->redirectToRoute('organization.presentation.dashboard');
            }

            $this->organizationDomainService->removeUserFromGroup($memberId, $group);
            $this->addFlash('success', "Member removed from {$group->getName()}.");
        } catch (Throwable $e) {
            $this->addFlash('error', 'Failed to remove member from group: ' . $e->getMessage());
        }

        return $this->redirectToRoute('organization.presentation.dashboard');
    }
}
