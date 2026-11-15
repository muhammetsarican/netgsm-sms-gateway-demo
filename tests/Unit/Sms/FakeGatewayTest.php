<?php

use App\Sms\Data\OutgoingSms;
use App\Sms\Gateways\FakeGateway;

it('mesajı kabul eder ve bellekte saklar', function () {
    $gateway = new FakeGateway(spyLogger());

    $result = $gateway->send(OutgoingSms::single('05321234567', 'Merhaba'));

    expect($result->accepted)->toBeTrue()->and($result->reference)->toStartWith('fake-')->and($gateway->sent())->toHaveCount(1);
});

it('loga açık numara ve mesaj metni yazmaz', function () {
    $logger = spyLogger();

    (new FakeGateway($logger))->send(OutgoingSms::single('05321234567', 'Kodunuz: 482910'));

    $logged = json_encode($logger->records, JSON_UNESCAPED_UNICODE);

    expect($logger->records)->toHaveCount(1)->and($logged)->not->toContain('5321234567')->and($logged)->not->toContain('482910')->and($logged)->toContain('+90 532 *** ** 67');
});
