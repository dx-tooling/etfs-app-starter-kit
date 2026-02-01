<?php

declare(strict_types=1);

namespace App\Tests\Integration\Account;

use App\Account\Domain\Service\AccountDomainServiceInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use ValueError;

final class AccountDomainServiceTest extends KernelTestCase
{
    private AccountDomainServiceInterface $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        /** @var AccountDomainServiceInterface $service */
        $service       = $container->get(AccountDomainServiceInterface::class);
        $this->service = $service;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testRegisterCreatesAccountWithEmail(): void
    {
        $email = 'test-' . uniqid() . '@example.com';

        $account = $this->service->register($email, 'password123');

        $this->assertNotNull($account->getId());
        $this->assertSame($email, $account->getEmail());
    }

    public function testRegisterNormalizesEmailToLowercase(): void
    {
        $email = 'TEST-' . uniqid() . '@EXAMPLE.COM';

        $account = $this->service->register($email, 'password123');

        $this->assertSame(mb_strtolower($email), $account->getEmail());
    }

    public function testRegisterHashesPassword(): void
    {
        $email         = 'hash-test-' . uniqid() . '@example.com';
        $plainPassword = 'mypassword123';

        $account = $this->service->register($email, $plainPassword);

        $this->assertNotSame($plainPassword, $account->getPasswordHash());
        $this->assertNotEmpty($account->getPasswordHash());
    }

    public function testRegisterThrowsForDuplicateEmail(): void
    {
        $email = 'duplicate-' . uniqid() . '@example.com';
        $this->service->register($email, 'password123');

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage("Account with email '{$email}' already exists.");

        $this->service->register($email, 'differentpassword');
    }

    public function testRegisterSetsMustSetPasswordFlag(): void
    {
        $email = 'mustset-' . uniqid() . '@example.com';

        $account = $this->service->register($email, null, true);

        $this->assertTrue($account->getMustSetPassword());
    }

    public function testRegisterGeneratesRandomPasswordWhenNull(): void
    {
        $email = 'nullpw-' . uniqid() . '@example.com';

        $account = $this->service->register($email, null);

        $this->assertNotEmpty($account->getPasswordHash());
    }

    public function testFindByEmailReturnsAccount(): void
    {
        $email = 'findme-' . uniqid() . '@example.com';
        $this->service->register($email, 'password123');

        $found = $this->service->findByEmail($email);

        $this->assertNotNull($found);
        $this->assertSame($email, $found->getEmail());
    }

    public function testFindByEmailReturnsNullForUnknown(): void
    {
        $found = $this->service->findByEmail('nonexistent-' . uniqid() . '@example.com');

        $this->assertNull($found);
    }

    public function testVerifyPasswordReturnsTrueForCorrectPassword(): void
    {
        $email    = 'verify-' . uniqid() . '@example.com';
        $password = 'correctpassword';
        $account  = $this->service->register($email, $password);

        $this->assertTrue($this->service->verifyPassword($account, $password));
    }

    public function testVerifyPasswordReturnsFalseForWrongPassword(): void
    {
        $email   = 'verifywrong-' . uniqid() . '@example.com';
        $account = $this->service->register($email, 'correctpassword');

        $this->assertFalse($this->service->verifyPassword($account, 'wrongpassword'));
    }

    public function testUpdatePasswordChangesHash(): void
    {
        $email   = 'updatepw-' . uniqid() . '@example.com';
        $account = $this->service->register($email, 'oldpassword');
        $oldHash = $account->getPasswordHash();

        $this->service->updatePassword($account, 'newpassword');

        // Password hash changes after update
        $this->assertNotSame($oldHash, $account->getPasswordHash());
        // Can verify with new password
        $this->assertTrue($this->service->verifyPassword($account, 'newpassword'));
        // Old password no longer works
        $this->assertFalse($this->service->verifyPassword($account, 'oldpassword'));
    }

    public function testAccountCoreCanSignInWhenNull(): void
    {
        $this->assertTrue($this->service->accountCoreCanSignIn(null));
    }

    public function testAccountCoreCannotSignInWhenAlreadySignedIn(): void
    {
        $email   = 'signin-' . uniqid() . '@example.com';
        $account = $this->service->register($email, 'password');

        $this->assertFalse($this->service->accountCoreCanSignIn($account));
    }

    public function testAccountCoreCanSignOutWhenSignedIn(): void
    {
        $email   = 'signout-' . uniqid() . '@example.com';
        $account = $this->service->register($email, 'password');

        $this->assertTrue($this->service->accountCoreCanSignOut($account));
    }

    public function testAccountCoreCannotSignOutWhenNull(): void
    {
        $this->assertFalse($this->service->accountCoreCanSignOut(null));
    }

    public function testAccountCoreIsSignedInReturnsTrueWhenAccountExists(): void
    {
        $email   = 'issignedin-' . uniqid() . '@example.com';
        $account = $this->service->register($email, 'password');

        $this->assertTrue($this->service->accountCoreIsSignedIn($account));
    }

    public function testAccountCoreIsSignedInReturnsFalseWhenNull(): void
    {
        $this->assertFalse($this->service->accountCoreIsSignedIn(null));
    }
}
