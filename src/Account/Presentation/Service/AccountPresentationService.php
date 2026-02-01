<?php

declare(strict_types=1);

namespace App\Account\Presentation\Service;

use App\Account\Domain\Entity\AccountCore;
use Doctrine\ORM\EntityManagerInterface;
use ValueError;

readonly class AccountPresentationService implements AccountPresentationServiceInterface
{
    public function __construct(
        /*private MailService                           $mailService,
        private TranslatorInterface                   $translator,
        private EmailVerifier                         $emailVerifier,
        private RequestParametersBasedUserAuthService $requestParametersBasedUserAuthService,*/
        private EntityManagerInterface $entityManager
    ) {
    }

    public function sendVerificationEmailForClaimedAccountCore(
        AccountCore $accountCore
    ): void {
        if (!$accountCore->isRegistered()) {
            throw new ValueError(
                'Account is not registered.'
            );
        }

        if ($accountCore->isVerified()) {
            throw new ValueError(
                'Account is already verified.'
            );
        }

        // @todo add this part
        /*$this->emailVerifier->sendEmailAskingForVerification(
            'account.presentation.sign_up.email_verification',
            $accountCore,
            new TemplatedEmail()
                ->from($this->mailService->getDefaultSenderAddress())
                ->to($accountCore->getEmail())
                ->subject(
                    $this->translator->trans(
                        'claim_unregistered_user.form.cta',
                        [],
                        'etfs.account'
                    )
                )
                ->htmlTemplate(
                    '@videobasedmarketing.account/claim_unregistered_user/verify_claimed_account.email.html.twig'
                )
        );*/
    }

    public function sendPasswordResetEmail(
        string $email
    ): void {
        /** @var AccountCore|null $accountCore */
        $accountCore = $this->entityManager->getRepository(AccountCore::class)->findOneBy(['email' => $email]);

        if (is_null($accountCore)) {
            return;
        }

        /*$url = $this->requestParametersBasedUserAuthService->createUrl(
            $accountCore,
            'videobasedmarketing.account.presentation.password.change',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL,
            '+22 minutes'
        );

        $context['url'] = $url;

        $this->mailService->send(
            new TemplatedEmail()
                ->from($this->mailService->getDefaultSenderAddress())
                ->to($accountCore->getEmail())
                ->subject(
                    $this->translator->trans(
                        'sign_in.forgot_password.reset_password_email.subject',
                        [],
                        'etfs.account'
                    )
                )
                ->htmlTemplate(
                    '@videobasedmarketing.account/sign_in/forgot_password/reset_password.email.html.twig'
                )
                ->context($context)
        );*/
    }
}
