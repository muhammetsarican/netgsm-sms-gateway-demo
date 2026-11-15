<?php

declare(strict_types=1);

namespace App\Sms\Data;

use InvalidArgumentException;

final readonly class PhoneNumber
{
    private function __construct(public string $value) {}

    public static function from(string $raw): self
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        $national = match (true) {
            strlen($digits) === 12 && str_starts_with($digits, '90') => $digits,
            strlen($digits) === 11 && str_starts_with($digits, '0') => '9'.$digits,
            strlen($digits) === 10 => '90'.$digits,
            default => null,
        };

        if ($national === null || preg_match('/^905\d{9}$/', $national) !== 1) {
            throw new InvalidArgumentException('Geçersiz Türkiye cep telefonu numarası!');
        }

        return new self($national);
    }

    public static function tryFrom(string $input): ?self
    {
        try {
            return self::from($input);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    public function national(): string
    {
        return substr($this->value, 2);
    }

    public function masked(): string
    {
        return '+90 '.substr($this->value, 2, 3).' *** ** '.substr($this->value, -2);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
