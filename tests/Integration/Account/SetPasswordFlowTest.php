<?php

declare(strict_types=1);

namespace App\Tests\Integration\Account;

use App\Account\Domain\Service\AccountDomainServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests for the password setting flow, particularly for users created via invitation.
 */
final class SetPasswordFlowTest extends WebTestCase
{
    private AccountDomainServiceInterface $accountService;
    private EntityManagerInterface $entityManager;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container    = static::getContainer();

        /** @var AccountDomainServiceInterface $service */
        $service              = $container->get(AccountDomainServiceInterface::class);
        $this->accountService = $service;

        /** @var EntityManagerInterface $em */
        $em                  = $container->get(EntityManagerInterface::class);
        $this->entityManager = $em;
    }

    /**
     * Functional test: Calls the actual controller endpoint to verify that
     * the mustSetPassword flag is correctly persisted after setting a password.
     *
     * This test will FAIL if the controller has the bug where setMustSetPassword(false)
     * is called AFTER updatePassword() flushes.
     */
    public function testSetPasswordControllerPersistsMustSetPasswordFlag(): void
    {
        // Step 1: Create an account with mustSetPassword = true (simulating invitation flow)
        $email   = 'controller-test-' . uniqid() . '@example.com';
        $account = $this->accountService->register($email, null, true);

        $this->assertTrue($account->getMustSetPassword(), 'Account should have mustSetPassword=true after invitation registration');

        // Step 2: Log in as the user and call the set-password endpoint
        $this->client->loginUser($account);

        $this->client->request('POST', '/en/account/set-password', [
            'password'         => 'newSecurePassword123',
            'password_confirm' => 'newSecurePassword123',
        ]);

        // Should redirect to dashboard on success
        $this->assertResponseRedirects('/en/account/dashboard');

        // Step 3: Clear the entity manager to force a fresh load from database
        $this->entityManager->clear();

        // Step 4: Reload the account from database
        $reloadedAccount = $this->accountService->findByEmail($email);

        // Step 5: Verify the flag was actually persisted
        $this->assertNotNull($reloadedAccount, 'Account should exist in database');
        $this->assertFalse(
            $reloadedAccount->getMustSetPassword(),
            'mustSetPassword flag should be persisted as false after calling the set-password controller. ' .
            'If this fails, the controller has a bug where the flag change is not persisted.'
        );
    }

    /**
     * Regression test at the service layer: Ensures the buggy sequence
     * (updatePassword THEN setMustSetPassword) does NOT persist the flag change,
     * demonstrating why the correct order matters.
     */
    public function testBuggySequenceDoesNotPersistFlag(): void
    {
        // Step 1: Create an account with mustSetPassword = true
        $email   = 'buggy-sequence-' . uniqid() . '@example.com';
        $account = $this->accountService->register($email, null, true);

        $this->assertTrue($account->getMustSetPassword());

        // Step 2: Use the WRONG sequence (this was the bug):
        // updatePassword flushes BEFORE setMustSetPassword is called
        $this->accountService->updatePassword($account, 'newSecurePassword123');
        $account->setMustSetPassword(false);

        // In-memory it looks correct
        $this->assertFalse($account->getMustSetPassword());

        // Step 3: Clear and reload from database
        $this->entityManager->clear();
        $reloadedAccount = $this->accountService->findByEmail($email);

        // Step 4: The flag should still be true in the database (proving the bug)
        $this->assertNotNull($reloadedAccount);
        $this->assertTrue(
            $reloadedAccount->getMustSetPassword(),
            'With the buggy sequence, the flag change is NOT persisted - it remains true in DB'
        );
    }
}
