<?php

declare(strict_types=1);

use App\Account\Domain\Enum\Role;

describe('Role enum', function (): void {
    it('has USER role with correct value', function (): void {
        expect(Role::USER->value)->toBe('ROLE_USER');
    });

    it('has ADMIN role with correct value', function (): void {
        expect(Role::ADMIN->value)->toBe('ROLE_ADMIN');
    });

    it('has exactly two roles', function (): void {
        expect(Role::cases())->toHaveCount(2);
    });
});
