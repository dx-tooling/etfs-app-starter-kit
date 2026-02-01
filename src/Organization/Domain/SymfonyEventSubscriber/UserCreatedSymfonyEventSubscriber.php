<?php

declare(strict_types=1);

namespace App\Organization\Domain\SymfonyEventSubscriber;

use App\Account\Facade\SymfonyEvent\UserCreatedSymfonyEvent;
use App\Organization\Domain\Service\OrganizationDomainServiceInterface;
use App\Organization\Facade\SymfonyEvent\CurrentlyActiveOrganizationChangedSymfonyEvent;
use Exception;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsEventListener(event: UserCreatedSymfonyEvent::class, method: 'handle')]
readonly class UserCreatedSymfonyEventSubscriber
{
    public function __construct(
        private OrganizationDomainServiceInterface $organizationDomainService,
        private EventDispatcherInterface           $eventDispatcher
    ) {
    }

    /**
     * @throws Exception
     */
    public function handle(
        UserCreatedSymfonyEvent $event
    ): void {
        $organization = $this
            ->organizationDomainService
            ->createOrganization($event->userId);

        $this->eventDispatcher->dispatch(
            new CurrentlyActiveOrganizationChangedSymfonyEvent(
                $organization->getId(),
                $event->userId
            )
        );
    }
}
