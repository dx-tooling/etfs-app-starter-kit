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

        $organizationName = null;
        $currentlyActiveOrganizationsId = $user->getCurrentlyActiveOrganizationsId();

        if ($currentlyActiveOrganizationsId !== null) {
            $currentOrganization = $this->organizationDomainService->getOrganizationById($currentlyActiveOrganizationsId);
            if ($currentOrganization !== null) {
                $organizationName = $this->organizationDomainService->getOrganizationName($currentOrganization, null);
            }
        }

        return $this->render('@account.presentation/account_dashboard.html.twig', [
            'organizationName' => $organizationName,
        ]);
    }
}
