<?php

use App\Sms\Contracts\SmsGateway;
use App\Sms\Gateways\FakeGateway;

it('fake driver seçiliyken FakeGateway kullanılır', function () {
    config(['sms.driver' => 'fake']);

    expect(app(SmsGateway::class))->toBeInstanceOf(FakeGateway::class);
});

it('bilinmeyen driver açık bir hata verir', function () {
    config(['sms.driver' => 'olmayan-driver']);

    expect(fn () => app(SmsGateway::class))->toThrow(InvalidArgumentException::class, 'Bilinmeyen SMS driver: olmayan-driver');
});

it('netgsm driver kimlik bilgileri eksikse açılışta hata verir', function(){
  config([
    'sms.driver'=>'netgsm',
    'sms.netgsm.usercode'=>null,
  ]);

  expect(fn()=>app(SmsGateway::class))
  ->toThrow(InvalidArgumentException::class,'Netgsm yapılandırması eksik: sms.netgsm.usercode');
});