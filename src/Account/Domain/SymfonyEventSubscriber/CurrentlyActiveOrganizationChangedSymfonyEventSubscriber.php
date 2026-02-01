<?php

declare(strict_types=1);

namespace App\Account\Domain\SymfonyEventSubscriber;

use App\Account\Domain\Entity\AccountCore;
use App\Organization\Facade\SymfonyEvent\CurrentlyActiveOrganizationChangedSymfonyEvent;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Exception;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: CurrentlyActiveOrganizationChangedSymfonyEvent::class, method: 'handle')]
readonly class CurrentlyActiveOrganizationChangedSymfonyEventSubscriber
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @throws Exception|ORMException
     */
    public function handle(
        CurrentlyActiveOrganizationChangedSymfonyEvent $event
    ): void {
        $accountCore = $this->entityManager->find(AccountCore::class, $event->affectedUserId);

        if (is_null($accountCore)) {
            throw new Exception(
                'AccountCore with id ' . $event->affectedUserId . ' not found to set currently active organization with id ' . $event->organizationId . ' to.'
            );
        }

        $accountCore->setCurrentlyActiveOrganizationId($event->organizationId);

        $this->entityManager->persist($accountCore);
        $this->entityManager->flush();
    }
}
