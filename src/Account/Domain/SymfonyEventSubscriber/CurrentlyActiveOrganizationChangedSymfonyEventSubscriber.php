<?php

declare(strict_types=1);

namespace App\Account\Domain\SymfonyEventSubscriber;

use App\Account\Domain\Entity\User;
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
        $user = $this->entityManager->find(User::class, $event->affectedUserId);

        if (is_null($user)) {
            throw new Exception(
                'User with id ' . $event->affectedUserId . ' not found to set currently active organization with id ' . $event->organizationId . ' to.'
            );
        }

        $user->setCurrentlyActiveOrganizationId($event->organizationId);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
