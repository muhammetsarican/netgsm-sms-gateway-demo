<?php

use App\Sms\Data\PhoneNumber;
use InvalidArgumentException;

it('farklı yazımları aynı biçime çevirir', function (string $input) {
    expect(PhoneNumber::from($input)->value)->toBe('905321234567');
})->with([
    'başında sıfır' => '0532 123 45 67',
    'ülke kodlu' => '+90 (532) 123-45-67',
    'yalın' => '5321234567',
    'zaten normal' => '905321234567',
]);

it('cep telefonu olmayan numaraları reddeder', function (string $input) {
    expect(fn () => PhoneNumber::from($input))->toThrow(InvalidArgumentException::class);
})->with([
    'sabit hat' => '0312 123 45 67',
    'çok kısa' => '123',
    'boş' => '',
]);

it('tryFrom geçersiz numarada null döner', function () {
    expect(PhoneNumber::tryFrom('abc'))->toBeNull();
});

it('ulusal biçimi Netgsm formatında verir', function () {
    expect(PhoneNumber::from('05321234567')->national())->toBe('5321234567');
});

it('maskelenmiş hali numaranın ortasını gizler', function () {
    expect(PhoneNumber::from('05321234567')->masked())->toBe('+90 532 *** ** 67')->not->toContain('1234');
});
