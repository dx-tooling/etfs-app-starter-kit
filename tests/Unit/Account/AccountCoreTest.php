<?php

declare(strict_types=1);

use App\Account\Domain\Entity\AccountCore;
use App\Account\Domain\Enum\Role;

describe('AccountCore', function (): void {
    describe('email handling', function (): void {
        it('normalizes email to lowercase', function (): void {
            $account = new AccountCore('Test@Example.COM', 'hash');
            expect($account->getEmail())->toBe('test@example.com');
        });

        it('trims whitespace from email', function (): void {
            $account = new AccountCore('  user@example.com  ', 'hash');
            expect($account->getEmail())->toBe('user@example.com');
        });

        it('returns email as user identifier', function (): void {
            $account = new AccountCore('user@example.com', 'hash');
            expect($account->getUserIdentifier())->toBe('user@example.com');
        });

        it('throws LogicException when getting identifier with empty email', function (): void {
            $account = new AccountCore('', 'hash');
            expect(fn () => $account->getUserIdentifier())->toThrow(LogicException::class);
        });
    });

    describe('role management', function (): void {
        it('has ROLE_USER by default', function (): void {
            $account = new AccountCore('user@example.com', 'hash');
            expect($account->getRoles())->toContain(Role::USER->value);
        });

        it('always includes ROLE_USER in getRoles even if removed', function (): void {
            $account = new AccountCore('user@example.com', 'hash');
            $account->removeRole(Role::USER);
            expect($account->getRoles())->toContain(Role::USER->value);
        });

        it('can add ROLE_ADMIN', function (): void {
            $account = new AccountCore('user@example.com', 'hash');
            $account->addRole(Role::ADMIN);
            expect($account->getRoles())->toContain(Role::ADMIN->value);
        });

        it('does not duplicate roles when adding same role twice', function (): void {
            $account = new AccountCore('user@example.com', 'hash');
            $account->addRole(Role::ADMIN);
            $account->addRole(Role::ADMIN);

            $adminCount = count(array_filter(
                $account->getRoles(),
                fn (string $role): bool => $role === Role::ADMIN->value
            ));
            expect($adminCount)->toBe(1);
        });

        it('can remove a role', function (): void {
            $account = new AccountCore('user@example.com', 'hash');
            $account->addRole(Role::ADMIN);
            $account->removeRole(Role::ADMIN);
            expect($account->getRoles())->not->toContain(Role::ADMIN->value);
        });

        it('hasRole returns true for existing role', function (): void {
            $account = new AccountCore('user@example.com', 'hash');
            expect($account->hasRole(Role::USER))->toBeTrue();
        });

        it('hasRole returns false for non-existing role', function (): void {
            $account = new AccountCore('user@example.com', 'hash');
            expect($account->hasRole(Role::ADMIN))->toBeFalse();
        });
    });

    describe('admin check', function (): void {
        it('isAdmin returns false by default', function (): void {
            $account = new AccountCore('user@example.com', 'hash');
            expect($account->isAdmin())->toBeFalse();
        });

        it('isAdmin returns true when ROLE_ADMIN is present', function (): void {
            $account = new AccountCore('user@example.com', 'hash');
            $account->addRole(Role::ADMIN);
            expect($account->isAdmin())->toBeTrue();
        });
    });

    describe('registration and verification status', function (): void {
        it('isRegistered returns true when email is set', function (): void {
            $account = new AccountCore('user@example.com', 'hash');
            expect($account->isRegistered())->toBeTrue();
        });

        it('isRegistered returns false when email is empty', function (): void {
            $account = new AccountCore('', 'hash');
            expect($account->isRegistered())->toBeFalse();
        });

        it('isVerified returns true for registered accounts', function (): void {
            $account = new AccountCore('user@example.com', 'hash');
            expect($account->isVerified())->toBeTrue();
        });
    });

    describe('password handling', function (): void {
        it('stores password hash', function (): void {
            $account = new AccountCore('user@example.com', 'myhash123');
            expect($account->getPasswordHash())->toBe('myhash123');
        });

        it('getPassword returns password hash for Symfony compatibility', function (): void {
            $account = new AccountCore('user@example.com', 'myhash123');
            expect($account->getPassword())->toBe('myhash123');
        });

        it('can update password hash', function (): void {
            $account = new AccountCore('user@example.com', 'oldhash');
            $account->setPasswordHash('newhash');
            expect($account->getPasswordHash())->toBe('newhash');
        });
    });

    describe('mustSetPassword flag', function (): void {
        it('is false by default', function (): void {
            $account = new AccountCore('user@example.com', 'hash');
            expect($account->getMustSetPassword())->toBeFalse();
        });

        it('can be set to true', function (): void {
            $account = new AccountCore('user@example.com', 'hash');
            $account->setMustSetPassword(true);
            expect($account->getMustSetPassword())->toBeTrue();
        });
    });

    describe('currently active organization', function (): void {
        it('is null by default', function (): void {
            $account = new AccountCore('user@example.com', 'hash');
            expect($account->getCurrentlyActiveOrganizationId())->toBeNull();
        });

        it('can be set to an organization id', function (): void {
            $account = new AccountCore('user@example.com', 'hash');
            $orgId   = 'abc123-org-id';
            $account->setCurrentlyActiveOrganizationId($orgId);
            expect($account->getCurrentlyActiveOrganizationId())->toBe($orgId);
        });
    });
});
