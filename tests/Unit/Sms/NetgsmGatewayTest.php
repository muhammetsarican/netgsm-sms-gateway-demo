<?php

use App\Sms\Data\OutgoingSms;
use App\Sms\Exceptions\GatewayUnavailableException;
use App\Sms\Gateways\Netgsm\NetgsmGateway;
use Netgsm\Sms\SmsSend;

function fakeNetgsmClient(array $response, ?array &$captured = null): SmsSend
{
    $client = Mockery::mock(SmsSend::class);
    $client->shouldReceive('smsGonder')->andReturnUsing(function (array $payload) use (&$captured, $response) {
        $captured = $payload;

        return $response;
    });

    return $client;
}

function netgsmGateway(SmsSend $client, $logger = null): NetgsmGateway
{
    return new NetgsmGateway($client, $logger ?? spyLogger(), defaultHeader: 'DEMO');
}

it('Netgsm\'e doğru biçimde istek gönderir ve bulkid döner', function () {
    $client = fakeNetgsmClient(['code' => '00', 'bulkid' => '1311033503'], $payload);

    $result = netgsmGateway($client)->send(OutgoingSms::single('0532 123 45 67', 'Merhaba'));

    expect($result->accepted)->toBeTrue()
        ->and($result->reference)->toBe('1311033503')
        ->and($payload['no'])->toBe(['5321234567'])
        ->and($payload['header'])->toBe('DEMO')
        ->and($payload['filter'])->toBe('0')
        ->and($payload['encoding'])->toBeNull();
});

it('Türkçe karakter varsa TR encoding kullanır', function () {
    $client = fakeNetgsmClient(['code' => '00', 'bulkid' => '1'], $payload);

    netgsmGateway($client)->send(OutgoingSms::single('05321234567', 'Merhaba', header: 'KAMPANYA'));

    expect($payload['header'])->toBe('KAMPANYA');
});

it('mesajdaki başlık varsayılan başlığı ezer', function () {
    $client = fakeNetgsmClient(['code' => '00', 'bulkid' => '1'], $payload);

    netgsmGateway($client)->send(OutgoingSms::single('05321234567', 'Merhaba', header: 'KAMPANYA'));

    expect($payload['header'])->toBe('KAMPANYA');
});

it('ret kodlarını SendResult\'a çevirir', function (string $code, bool $retryable) {
    $result = netgsmGateway(fakeNetgsmClient(['code' => $code]))->send(OutgoingSms::single('05321234567', 'Merhaba'));

    expect($result->accepted)->toBeFalse()->and($result->errorCode)->toBe($code)->and($result->retryable)->toBe($retryable);

})->with([
    'tanımsız başlık' => ['40', false],
    'kimlik hatası' => ['30', false],
    'sınır aşımı' => ['80', true],
]);

it('ağ hatasını GatewayUnavailableException\'a çevirir', function () {
    $client = Mockery::mock(SmsSend::class);
    $client->shouldReceive('smsGonder')->andThrow(new RuntimeException('Connection timed out'));

    expect(fn () => netgsmGateway($client)->send(OutgoingSms::single('05321234567', 'Merhaba')))->toThrow(GatewayUnavailableException::class);
});

it('tanınmayan yanıtta exception fırlatır', function (array $response) {
    expect(fn () => netgsmGateway(fakeNetgsmClient($response))->send(OutgoingSms::single('05321234567', 'Merhaba')))->toThrow(GatewayUnavailableException::class);
})->with([
    'bilinmeyen kod' => [['code' => '99']],
    'kod yok' => [[]],
    'başarılı ama bulkid yok' => [['code' => '00']],
]);

it('loga açık numara ve mesaj metni yazmaz', function (){
  $logger=spyLogger();

  netgsmGateway(fakeNetgsmClient(['code'=>'00', 'bulkid'=>'1']),$logger)->send(OutgoingSms::single('05321234567','Kodunuz: 482910'));

  $logged=json_encode($logger->records, JSON_UNESCAPED_UNICODE);

  expect($logged)->not->toContain('05321234567')->and($logged)->not->toContain('482910');
});
