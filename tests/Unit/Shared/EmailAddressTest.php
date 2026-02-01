<?php

declare(strict_types=1);

use App\Shared\Facade\ValueObject\EmailAddress;

describe('EmailAddress', function (): void {
    it('normalizes to lowercase and trims whitespace', function (): void {
        $email = EmailAddress::fromString('  Test@Example.COM  ');

        expect((string) $email)->toBe('test@example.com');
    });

    it('throws for empty string', function (): void {
        expect(static fn (): EmailAddress => EmailAddress::fromString(''))->toThrow(InvalidArgumentException::class);
    });

    it('throws for invalid format', function (): void {
        expect(static fn (): EmailAddress => EmailAddress::fromString('not-an-email'))->toThrow(InvalidArgumentException::class);
    });
});
