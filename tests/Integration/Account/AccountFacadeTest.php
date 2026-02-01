<?php

declare(strict_types=1);

namespace App\Tests\Integration\Account;

use App\Account\Facade\AccountFacadeInterface;
use App\Account\Facade\Dto\UserRegistrationDto;
use App\Shared\Facade\ValueObject\EmailAddress;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AccountFacadeTest extends KernelTestCase
{
    private AccountFacadeInterface $facade;

    protected function setUp(): void
    {
        self::bootKernel();

        /** @var AccountFacadeInterface $facade */
        $facade       = static::getContainer()->get(AccountFacadeInterface::class);
        $this->facade = $facade;
    }

    public function testRegisterReturnsSuccessResult(): void
    {
        $email = 'facade-' . uniqid() . '@example.com';
        $dto   = new UserRegistrationDto(
            EmailAddress::fromString($email),
            'password123',
            false
        );

        $result = $this->facade->register($dto);

        $this->assertTrue($result->isSuccess);
        $this->assertNull($result->errorMessage);
        $this->assertNotNull($result->userId);
    }

    public function testRegisterReturnsFailureForDuplicateEmail(): void
    {
        $email = 'facadedupe-' . uniqid() . '@example.com';
        $dto   = new UserRegistrationDto(
            EmailAddress::fromString($email),
            'password123',
            false
        );

        $this->facade->register($dto);
        $result = $this->facade->register($dto);

        $this->assertFalse($result->isSuccess);
        $this->assertNotNull($result->errorMessage);
        $this->assertNull($result->userId);
    }

    public function testGetAccountCoreIdByEmailReturnsId(): void
    {
        $email = 'getbyemail-' . uniqid() . '@example.com';
        $dto   = new UserRegistrationDto(
            EmailAddress::fromString($email),
            'password123',
            false
        );
        $result = $this->facade->register($dto);

        $foundId = $this->facade->getAccountCoreIdByEmail($email);

        $this->assertSame($result->userId, $foundId);
    }

    public function testGetAccountCoreIdByEmailNormalizesCase(): void
    {
        $email = 'casetest-' . uniqid() . '@example.com';
        $dto   = new UserRegistrationDto(
            EmailAddress::fromString($email),
            'password123',
            false
        );
        $result = $this->facade->register($dto);

        $foundId = $this->facade->getAccountCoreIdByEmail(mb_strtoupper($email));

        $this->assertSame($result->userId, $foundId);
    }

    public function testGetAccountCoreIdByEmailReturnsNullForUnknown(): void
    {
        $foundId = $this->facade->getAccountCoreIdByEmail('unknown-' . uniqid() . '@example.com');

        $this->assertNull($foundId);
    }

    public function testAccountCoreWithIdExistsReturnsTrue(): void
    {
        $email = 'exists-' . uniqid() . '@example.com';
        $dto   = new UserRegistrationDto(
            EmailAddress::fromString($email),
            'password123',
            false
        );
        $result = $this->facade->register($dto);
        self::assertNotNull($result->userId, 'User ID should be set after registration');

        $this->assertTrue($this->facade->accountCoreWithIdExists($result->userId));
    }

    public function testAccountCoreWithIdExistsReturnsFalseForUnknown(): void
    {
        $this->assertFalse($this->facade->accountCoreWithIdExists('nonexistent-id-12345'));
    }

    public function testGetAccountCoreEmailById(): void
    {
        $email = 'emailbyid-' . uniqid() . '@example.com';
        $dto   = new UserRegistrationDto(
            EmailAddress::fromString($email),
            'password123',
            false
        );
        $result = $this->facade->register($dto);
        self::assertNotNull($result->userId, 'User ID should be set after registration');

        $foundEmail = $this->facade->getAccountCoreEmailById($result->userId);

        $this->assertSame($email, $foundEmail);
    }

    public function testGetAccountCoreEmailByIdReturnsNullForUnknown(): void
    {
        $foundEmail = $this->facade->getAccountCoreEmailById('nonexistent-id-12345');

        $this->assertNull($foundEmail);
    }

    public function testGetAccountCoreInfoByIdsReturnsMatchingAccounts(): void
    {
        $email1 = 'info1-' . uniqid() . '@example.com';
        $email2 = 'info2-' . uniqid() . '@example.com';

        $result1 = $this->facade->register(new UserRegistrationDto(
            EmailAddress::fromString($email1),
            'password123',
            false
        ));
        self::assertNotNull($result1->userId, 'User ID should be set after registration');

        $result2 = $this->facade->register(new UserRegistrationDto(
            EmailAddress::fromString($email2),
            'password123',
            false
        ));
        self::assertNotNull($result2->userId, 'User ID should be set after registration');

        $infos = $this->facade->getAccountCoreInfoByIds([$result1->userId, $result2->userId]);

        $this->assertCount(2, $infos);
        $emails = array_map(fn (\App\Account\Facade\Dto\AccountInfoDto $info): string => $info->email, $infos);
        $this->assertContains($email1, $emails);
        $this->assertContains($email2, $emails);
    }

    public function testGetAccountCoreInfoByIdsReturnsEmptyForEmptyInput(): void
    {
        $infos = $this->facade->getAccountCoreInfoByIds([]);

        $this->assertSame([], $infos);
    }

    public function testGetCurrentlyActiveOrganizationIdReturnsValueAfterRegistration(): void
    {
        $email = 'activeorg-' . uniqid() . '@example.com';
        $dto   = new UserRegistrationDto(
            EmailAddress::fromString($email),
            'password123',
            false
        );
        $result = $this->facade->register($dto);
        self::assertNotNull($result->userId, 'User ID should be set after registration');

        // Note: The AccountCoreCreatedSymfonyEvent sets this automatically,
        // so we expect it to be non-null after registration
        $orgId = $this->facade->getCurrentlyActiveOrganizationIdForAccountCore($result->userId);

        // After registration, an organization is automatically created and set as active
        $this->assertNotNull($orgId);
    }

    public function testGetAccountCoreForLoginReturnsUserInterface(): void
    {
        $email = 'login-' . uniqid() . '@example.com';
        $dto   = new UserRegistrationDto(
            EmailAddress::fromString($email),
            'password123',
            false
        );
        $result = $this->facade->register($dto);
        self::assertNotNull($result->userId, 'User ID should be set after registration');

        $user = $this->facade->getAccountCoreForLogin($result->userId);

        $this->assertNotNull($user);
        $this->assertSame($email, $user->getUserIdentifier());
    }
}
