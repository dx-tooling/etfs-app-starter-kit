<?php

declare(strict_types=1);

namespace App\Account\Presentation\Controller;

use App\Account\Domain\Entity\AccountCore;
use App\Account\Domain\Service\AccountDomainService;
use App\Organization\Facade\OrganizationFacadeInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

final class AccountController extends AbstractController
{
    public function __construct(
        private readonly AccountDomainService        $accountService,
        private readonly OrganizationFacadeInterface $organizationFacade,
        private readonly TranslatorInterface         $translator,
        private readonly Security                    $security
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

        $formData = [
            'email'    => '',
            'password' => '',
        ];

        if ($request->isMethod(Request::METHOD_POST)) {
            $email           = $request->request->get('email');
            $password        = $request->request->get('password');
            $passwordConfirm = $request->request->get('password_confirm');

            $formData['email']    = (string) $email;
            $formData['password'] = (string) $password;

            if (!$email || !$password) {
                $this->addFlash('error', $this->translator->trans('flash.error.missing_credentials', [], 'account'));

                return $this->render('@account.presentation/sign_up.html.twig', $formData);
            }

            if ($password !== $passwordConfirm) {
                $this->addFlash('error', $this->translator->trans('flash.error.passwords_mismatch', [], 'account'));

                return $this->render('@account.presentation/sign_up.html.twig', $formData);
            }

            try {
                $accountCore = $this->accountService->register((string) $email, (string) $password);
                $this->security->login($accountCore, 'form_login', 'main');

                return $this->redirectToRoute('account.presentation.dashboard');
            } catch (Throwable $e) {
                $this->addFlash('error', $e->getMessage());

                return $this->render('@account.presentation/sign_up.html.twig', $formData);
            }
        }

        return $this->render('@account.presentation/sign_up.html.twig', $formData);
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
        /** @var AccountCore|null $accountCore */
        $accountCore = $this->getUser();

        if ($accountCore === null) {
            return $this->redirectToRoute('account.presentation.sign_in');
        }

        if ($accountCore->getMustSetPassword()) {
            return $this->redirectToRoute('account.presentation.set_password');
        }

        $currentlyActiveOrganizationId = $accountCore->getCurrentlyActiveOrganizationId();
        $organizationName              = null;

        if ($currentlyActiveOrganizationId !== null) {
            $organizationName = $this->organizationFacade->getOrganizationNameById($currentlyActiveOrganizationId);
        }

        return $this->render('@account.presentation/account_dashboard.html.twig', [
            'organizationName' => $organizationName,
        ]);
    }

    #[Route(
        path: '/account/set-password',
        name: 'account.presentation.set_password',
        methods: [Request::METHOD_GET, Request::METHOD_POST]
    )]
    public function setPasswordAction(Request $request): Response
    {
        /** @var AccountCore|null $accountCore */
        $accountCore = $this->getUser();

        if ($accountCore === null) {
            return $this->redirectToRoute('account.presentation.sign_in');
        }

        // If user doesn't need to set password, redirect to dashboard
        if (!$accountCore->getMustSetPassword()) {
            return $this->redirectToRoute('account.presentation.dashboard');
        }

        if ($request->isMethod(Request::METHOD_POST)) {
            if (!$this->isCsrfTokenValid('set_password', $request->request->getString('_csrf_token'))) {
                $this->addFlash('error', $this->translator->trans('flash.error.invalid_csrf', [], 'account'));

                return $this->render('@account.presentation/set_password.html.twig');
            }

            $password        = $request->request->get('password');
            $passwordConfirm = $request->request->get('password_confirm');

            if (!$password) {
                $this->addFlash('error', $this->translator->trans('flash.error.password_required', [], 'account'));

                return $this->render('@account.presentation/set_password.html.twig');
            }

            if ($password !== $passwordConfirm) {
                $this->addFlash('error', $this->translator->trans('flash.error.passwords_mismatch', [], 'account'));

                return $this->render('@account.presentation/set_password.html.twig');
            }

            $accountCore->setMustSetPassword(false);
            $this->accountService->updatePassword($accountCore, (string) $password);
            $refreshedAccount = $this->accountService->findByEmail($accountCore->getEmail());

            if ($refreshedAccount !== null) {
                $this->security->login($refreshedAccount, 'form_login', 'main');
            }
            $this->addFlash('success', $this->translator->trans('flash.success.password_set', [], 'account'));

            return $this->redirectToRoute('account.presentation.dashboard');
        }

        return $this->render('@account.presentation/set_password.html.twig');
    }
}
