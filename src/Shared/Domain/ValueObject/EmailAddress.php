<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use InvalidArgumentException;

final readonly class EmailAddress
{
    private string $value;

    private function __construct(string $value)
    {
        $normalizedValue = self::normalize($value);

        if (!self::isValid($normalizedValue)) {
            throw new InvalidArgumentException(
                sprintf('Invalid email address: "%s"', $value)
            );
        }

        $this->value = $normalizedValue;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private static function normalize(string $value): string
    {
        return trim(mb_strtolower($value));
    }

    private static function isValid(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
}
