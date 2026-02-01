<?php

declare(strict_types=1);

namespace App\Tests\Integration\Common;

use App\Common\Presentation\Enum\AppNotificationType;
use App\Common\Presentation\Service\AppNotificationsPresentationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AppNotificationsPresentationServiceTest extends KernelTestCase
{
    private AppNotificationsPresentationService $service;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        /** @var AppNotificationsPresentationService $service */
        $service       = $container->get(AppNotificationsPresentationService::class);
        $this->service = $service;

        /** @var EntityManagerInterface $entityManager */
        $entityManager       = $container->get(EntityManagerInterface::class);
        $this->entityManager = $entityManager;

        $this->entityManager->getConnection()->executeStatement('DELETE FROM app_notifications');
    }

    public function testCreateNotificationIncrementsUnreadCount(): void
    {
        $this->assertSame(0, $this->service->getNumberOfUnreadAppNotifications());

        $this->service->createAppNotification(AppNotificationType::GenericText, 'Hello');

        $this->assertSame(1, $this->service->getNumberOfUnreadAppNotifications());
    }

    public function testMarkAllAsReadResetsUnreadCount(): void
    {
        $this->service->createAppNotification(AppNotificationType::GenericText, 'A');
        $this->service->createAppNotification(AppNotificationType::GenericText, 'B');

        $this->assertSame(2, $this->service->getNumberOfUnreadAppNotifications());

        $this->service->markAllAppNotificationsAsRead();

        $this->assertSame(0, $this->service->getNumberOfUnreadAppNotifications());
    }

    public function testGetLatestReturnsMostRecentFirst(): void
    {
        $this->service->createAppNotification(AppNotificationType::GenericText, 'First');
        $this->service->createAppNotification(AppNotificationType::GenericText, 'Second');

        $latest = $this->service->getLatestAppNotifications();

        $this->assertCount(2, $latest);
        $this->assertSame('Second', $latest[0]->getMessage());
        $this->assertSame('First', $latest[1]->getMessage());
    }
}
