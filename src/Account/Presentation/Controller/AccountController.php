<?php

declare(strict_types=1);

namespace App\Account\Presentation\Controller;

use App\Account\Domain\Entity\User;
use App\Account\Domain\Service\AccountDomainService;
use App\Account\Facade\AccountFacadeInterface;
use App\Organization\Domain\Service\OrganizationDomainServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Throwable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class AccountController extends AbstractController
{
    public function __construct(
        private readonly AccountDomainService               $accountService,
        private readonly OrganizationDomainServiceInterface $organizationDomainService,
        private readonly AccountFacadeInterface             $accountFacade
    ) {
    }

    #[Route(
        path: '/account/sign-in',
        name: 'account.presentation.sign_in',
        methods: [Request::METHOD_GET, Request::METHOD_POST]
    )]
    public function signInAction(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('account.presentation.dashboard');
        }

        $error        = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('@account.presentation/sign_in.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }

    #[Route(
        path: '/account/sign-up',
        name: 'account.presentation.sign_up',
        methods: [Request::METHOD_GET, Request::METHOD_POST]
    )]
    public function signUpAction(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('account.presentation.dashboard');
        }

        if ($request->isMethod(Request::METHOD_POST)) {
            $email    = $request->request->get('email');
            $password = $request->request->get('password');

            if (!$email || !$password) {
                $this->addFlash('error', 'Please provide both email and password.');

                return $this->render('@account.presentation/sign_up.html.twig');
            }

            try {
                $this->accountService->register((string) $email, (string) $password);
                $this->addFlash('success', 'Registration successful. Please sign in.');

                return $this->redirectToRoute('account.presentation.sign_in');
            } catch (Throwable $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('@account.presentation/sign_up.html.twig');
    }

    #[Route(
        path: '/account/sign-out',
        name: 'account.presentation.sign_out',
        methods: [Request::METHOD_GET, Request::METHOD_POST]
    )]
    public function signOutAction(): void
    {
        // This method can be empty - it will be intercepted by the logout key on your firewall
    }

    #[Route(
        path: '/account/dashboard',
        name: 'account.presentation.dashboard',
        methods: [Request::METHOD_GET]
    )]
    public function dashboardAction(): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if ($user === null) {
            return $this->redirectToRoute('account.presentation.sign_in');
        }

        $userId = $user->getId();

        $organizationName = null;
        $currentOrganization = null;
        $currentlyActiveOrganizationsId = $user->getCurrentlyActiveOrganizationsId();

        if ($currentlyActiveOrganizationsId !== null) {
            $currentOrganization = $this->organizationDomainService->getOrganizationById($currentlyActiveOrganizationsId);
            if ($currentOrganization !== null) {
                $organizationName = $this->organizationDomainService->getOrganizationName($currentOrganization, null);
            }
        }

        // Get all organizations for switching
        $allOrganizations = $this->organizationDomainService->getAllOrganizationsForUser($userId);
        $canSwitchOrganizations = $this->organizationDomainService->userCanSwitchOrganizations($userId);

        // Check if user can rename/invite for current organization (owner of active org)
        $canRenameCurrentOrganization = false;
        $canInviteToCurrentOrganization = false;
        $currentOrganizationRawName = null;
        $pendingInvitations = [];
        $members = [];

        if ($currentOrganization !== null) {
            $isOwner = $currentOrganization->getOwningUsersId() === $userId;
            $canRenameCurrentOrganization = $isOwner;
            $canInviteToCurrentOrganization = $isOwner;
            $currentOrganizationRawName = $currentOrganization->getName();

            // Get pending invitations if owner
            if ($isOwner) {
                $invitations = $this->organizationDomainService->getPendingInvitations($currentOrganization);
                foreach ($invitations as $invitation) {
                    $pendingInvitations[] = [
                        'id' => $invitation->getId(),
                        'email' => $invitation->getEmail(),
                        'createdAt' => $invitation->getCreatedAt(),
                    ];
                }
            }

            // Get members of the organization
            $memberIds = $this->organizationDomainService->getAllUserIdsForOrganization($currentOrganization);
            $ownerUserId = $currentOrganization->getOwningUsersId();

            // Include owner in the list if not already
            if (!in_array($ownerUserId, $memberIds, true)) {
                $memberIds[] = $ownerUserId;
            }

            // Get all groups for this organization
            $orgGroups = $this->organizationDomainService->getGroups($currentOrganization);

            // Build a map of userId -> groupIds for quick lookup
            $userGroupMap = [];
            foreach ($orgGroups as $group) {
                $groupMemberIds = $this->organizationDomainService->getGroupMemberIds($group);
                foreach ($groupMemberIds as $memberId) {
                    if (!isset($userGroupMap[$memberId])) {
                        $userGroupMap[$memberId] = [];
                    }
                    $userGroupMap[$memberId][] = $group->getId();
                }
            }

            $memberInfos = $this->accountFacade->getUserInfoByIds($memberIds);
            foreach ($memberInfos as $memberInfo) {
                $members[] = [
                    'id' => $memberInfo->id,
                    'displayName' => $memberInfo->getDisplayName(),
                    'email' => $memberInfo->email,
                    'isOwner' => $memberInfo->id === $ownerUserId,
                    'isCurrentUser' => $memberInfo->id === $userId,
                    'joinedAt' => $memberInfo->createdAt,
                    'groupIds' => $userGroupMap[$memberInfo->id] ?? [],
                ];
            }

            // Sort: owner first, then by display name
            usort($members, function ($a, $b) {
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
                'id' => $org->getId(),
                'name' => $this->organizationDomainService->getOrganizationName($org, null),
                'isOwned' => $org->getOwningUsersId() === $userId,
                'isActive' => $currentOrganization !== null && $org->getId() === $currentOrganization->getId(),
            ];
        }

        // Build groups list
        $groups = [];
        if ($currentOrganization !== null) {
            $orgGroups = $this->organizationDomainService->getGroups($currentOrganization);
            foreach ($orgGroups as $group) {
                $groups[] = [
                    'id' => $group->getId(),
                    'name' => $group->getName(),
                    'isDefault' => $group->isDefaultForNewMembers(),
                ];
            }
        }

        return $this->render('@account.presentation/account_dashboard.html.twig', [
            'organizationName' => $organizationName,
            'organizations' => $organizations,
            'canSwitchOrganizations' => $canSwitchOrganizations,
            'currentOrganizationId' => $currentlyActiveOrganizationsId,
            'canRenameCurrentOrganization' => $canRenameCurrentOrganization,
            'canInviteToCurrentOrganization' => $canInviteToCurrentOrganization,
            'currentOrganizationRawName' => $currentOrganizationRawName,
            'pendingInvitations' => $pendingInvitations,
            'members' => $members,
            'groups' => $groups,
            'isOrganizationOwner' => $currentOrganization !== null && $currentOrganization->getOwningUsersId() === $userId,
        ]);
    }
}
