<?php

use App\Sms\Data\OutgoingSms;
use App\Sms\Data\PhoneNumber;

it('alıcısız mesaj oluşturulamaz', function () {
    expect(fn () => new OutgoingSms([], 'Merhaba'))->toThrow(InvalidArgumentException::class, 'En az bir alıcı gerekli');
});

it('boş metinli mesaj oluşturulamaz', function () {
    expect(fn () => OutgoingSms::single('05321234567', '   '))->toThrow(InvalidArgumentException::class, 'Mesaj metni boş olamaz');
});

it('single() string numarayı PhoneNumber nesnesine çevirir', function () {
    $sms = OutgoingSms::single('0532 123 45 67', 'Merhaba');

    expect($sms->recipients)->toHaveCount(1)->and($sms->recipients[0])->toBeInstanceOf(PhoneNumber::class)->and($sms->recipients[0]->value)->toBe('905321234567');
});
