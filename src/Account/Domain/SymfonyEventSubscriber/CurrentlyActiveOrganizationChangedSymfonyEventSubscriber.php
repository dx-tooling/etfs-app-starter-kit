<?php

namespace App\Account\Domain\SymfonyEventSubscriber;

use App\Account\Domain\Entity\User;
use App\Organization\Domain\SymfonyEvent\CurrentlyActiveOrganizationChangedSymfonyEvent;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class CurrentlyActiveOrganizationChangedSymfonyEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CurrentlyActiveOrganizationChangedSymfonyEvent::class => [
                ['handle']
            ],
        ];
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

        $user->setCurrentlyActiveOrganizationsId($event->organizationId);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
