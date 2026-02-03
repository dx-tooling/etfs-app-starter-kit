<?php

declare(strict_types=1);

namespace App\Tests\Application\Organization;

use App\Account\Facade\AccountFacadeInterface;
use App\Account\Facade\Dto\UserRegistrationDto;
use App\Organization\Domain\Entity\Invitation;
use App\Organization\Infrastructure\Repository\OrganizationRepositoryInterface;
use App\Shared\Facade\ValueObject\EmailAddress;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class AcceptInvitationFlowTest extends WebTestCase
{
    private function getCsrfToken(string $tokenId): string
    {
        $container = static::getContainer();

        /** @var CsrfTokenManagerInterface $csrfTokenManager */
        $csrfTokenManager = $container->get(CsrfTokenManagerInterface::class);

        return $csrfTokenManager->getToken($tokenId)->getValue();
    }

    public function testAcceptInvitationCreatesUserAndRedirectsToSetPassword(): void
    {
        $client    = static::createClient();
        $container = static::getContainer();

        /** @var AccountFacadeInterface $accountFacade */
        $accountFacade = $container->get(AccountFacadeInterface::class);

        /** @var OrganizationRepositoryInterface $organizationRepository */
        $organizationRepository = $container->get(OrganizationRepositoryInterface::class);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);

        $ownerEmail  = 'owner-' . uniqid() . '@example.com';
        $ownerResult = $accountFacade->register(new UserRegistrationDto(
            EmailAddress::fromString($ownerEmail),
            'password123',
            false
        ));
        self::assertNotNull($ownerResult->userId, 'Owner registration must return a userId');

        $ownerOrgId = $accountFacade->getCurrentlyActiveOrganizationIdForAccountCore($ownerResult->userId);
        self::assertNotNull($ownerOrgId, 'Owner must have an active organization');

        $organization = $organizationRepository->findById($ownerOrgId);
        self::assertNotNull($organization, 'Organization must exist');

        $inviteeEmail = 'invitee-' . uniqid() . '@example.com';
        $invitation   = new Invitation($organization, $inviteeEmail);
        $entityManager->persist($invitation);
        $entityManager->flush();

        $invitationId = $invitation->getId();
        self::assertNotNull($invitationId);

        $client->request('GET', "/en/organization/invitation/{$invitationId}");
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');

        $client->request('POST', "/en/organization/invitation/{$invitationId}", [
            '_csrf_token' => $this->getCsrfToken('accept_invitation'),
        ]);
        $this->assertResponseRedirects('/en/account/set-password');

        $inviteeUserId = $accountFacade->getAccountCoreIdByEmail($inviteeEmail);
        self::assertNotNull($inviteeUserId, 'Invitee account must be created');

        $this->assertTrue(
            $organizationRepository->userHasJoinedOrganization($inviteeUserId, $ownerOrgId),
            'Invitee must be a member of the inviting organization'
        );
    }

    public function testAcceptInvitationRejectsInvalidCsrf(): void
    {
        $client    = static::createClient();
        $container = static::getContainer();

        /** @var AccountFacadeInterface $accountFacade */
        $accountFacade = $container->get(AccountFacadeInterface::class);

        /** @var OrganizationRepositoryInterface $organizationRepository */
        $organizationRepository = $container->get(OrganizationRepositoryInterface::class);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);

        /** @var UrlGeneratorInterface $urlGenerator */
        $urlGenerator = $container->get(UrlGeneratorInterface::class);

        $ownerEmail  = 'owner-invalid-' . uniqid() . '@example.com';
        $ownerResult = $accountFacade->register(new UserRegistrationDto(
            EmailAddress::fromString($ownerEmail),
            'password123',
            false
        ));
        self::assertNotNull($ownerResult->userId, 'Owner registration must return a userId');

        $ownerOrgId = $accountFacade->getCurrentlyActiveOrganizationIdForAccountCore($ownerResult->userId);
        self::assertNotNull($ownerOrgId, 'Owner must have an active organization');

        $organization = $organizationRepository->findById($ownerOrgId);
        self::assertNotNull($organization, 'Organization must exist');

        $inviteeEmail = 'invitee-invalid-' . uniqid() . '@example.com';
        $invitation   = new Invitation($organization, $inviteeEmail);
        $entityManager->persist($invitation);
        $entityManager->flush();

        $invitationId = $invitation->getId();
        self::assertNotNull($invitationId);

        $client->request('GET', "/en/organization/invitation/{$invitationId}");
        $this->assertResponseIsSuccessful();

        $client->request('POST', "/en/organization/invitation/{$invitationId}", [
            '_csrf_token' => 'invalid-token',
        ]);

        $this->assertResponseRedirects($urlGenerator->generate('content.presentation.homepage'));

        $inviteeUserId = $accountFacade->getAccountCoreIdByEmail($inviteeEmail);
        self::assertNull($inviteeUserId, 'Invitee account must not be created with invalid CSRF.');
    }
}
