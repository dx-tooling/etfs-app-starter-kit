<?php

namespace App\Organization\Domain\SymfonyEventSubscriber;

use App\Account\Domain\SymfonyEvent\UserCreatedSymfonyEvent;
use App\Organization\Domain\Service\OrganizationDomainServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class UserCreatedSymfonyEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private OrganizationDomainServiceInterface $organizationDomainService,
        private EntityManagerInterface             $entityManager
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserCreatedSymfonyEvent::class => [
                ['handle']
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function handle(
        UserCreatedSymfonyEvent $event
    ): void {
        $organization = $this
            ->organizationDomainService
            ->createOrganization($event->user);

        $event
            ->user
            ->setCurrentlyActiveOrganization($organization);

        $this->entityManager->persist($event->user);
        $this->entityManager->flush();
    }
}
