<?php

namespace App\Shared\Presentation\Service;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Message;

readonly class MailService
{
    public function __construct(
        private MailerInterface       $mailer,
        private ContainerBagInterface $containerBag
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function send(
        Message $email,
        bool    $autoresponserProtection = true
    ): void {
        if ($autoresponserProtection) {
            // this non-standard header tells compliant autoresponders ("email holiday mode")
            // to not reply to this message because it's an automated email
            $email->setHeaders(
                $email
                    ->getHeaders()
                    ->addTextHeader(
                        'X-Auto-Response-Suppress',
                        'OOF, DR, RN, NRN, AutoReply'
                    )
            );
        }

        $this->mailer->send($email);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getDefaultSenderAddress(): Address
    {
        return new Address(
            $this->containerBag->get('app.mail.default_sender_address'),
            'ETFS'
        );
    }
}
