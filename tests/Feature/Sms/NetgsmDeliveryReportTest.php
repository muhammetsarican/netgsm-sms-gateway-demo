<?php

use App\Sms\Data\PhoneNumber;
use App\Sms\Enums\MessageStatus;
use App\Sms\Exceptions\GatewayUnavailableException;
use App\Sms\Gateways\Netgsm\NetgsmGateway;
use Netgsm\Sms\SmsSend;

function fakeReportClient(array $response, ?array &$captured = null): NetgsmGateway
{
    $client = Mockery::mock(SmsSend::class);
    $client->shouldReceive('smsSorgulama')->andReturnUsing(function (array $query) use (&$captured, $response) {
        $captured = $query;

        return $response;
    });

    return new NetgsmGateway($client, spyLogger(), defaultSender: 'DEMO');
}

function recipients(): array
{
    return [PhoneNumber::from('05321234567'), PhoneNumber::from('05331234567')];
}

it('bulkid ile sorgu yapar', function () {
    fakeReportClient(['code' => '60'], $query)->report('1311033503', recipients());

    expect($query['bulkid'])->toBe('1311033503')->and($query['type'])->toBe(0)->and($query['status'])->toBe(100);
});

it('tek kayıtlı teslim raporunu çevirir', function () {
    $reports = fakeReportClient([
        'durumcode' => '1',
        'cepno' => '905321234567',
        'tarih' => '23.01.2023 09:35:00',
    ])->report('1', recipients());

    expect($reports)->toHaveCount(1)->and($reports[0]->status)->toBe(MessageStatus::Delivered)->and($reports[0]->phone->value)->toBe('905321234567')->and($reports[0]->reportedAt?->format('Y-m-d H:i'))->toBe('2023-01-23 09:35');
});

it('çok kayıtlı raporda her alıcıyı ayrı çevirir', function () {
    $reports = fakeReportClient([
        ['durumcode' => '1', 'cepno' => '905321234567'],
        ['durumcode' => '3', 'cepno' => '905331234567'],
        ['durumcode' => '0', 'cepno' => '905341234567'],
    ])->report('1', recipients());

    expect($reports)->toHaveCount(3)->and($reports[0]->status)->toBe(MessageStatus::Delivered)
        ->and($reports[1]->status)->toBe(MessageStatus::Failed)
        ->and($reports[1]->errorCode)->toBe('3')
        ->and($reports[2]->status)->toBe(MessageStatus::Sent);
});

it('henüz kayıt yoksa (60) boş liste döner', function () {
    expect(fakeReportClient(['code' => '60'])->report('1', recipients()))->toBe([]);
});

it('diğer hata kodlarında exception fırlatır', function () {
    expect(fn () => fakeReportClient(['code' => '30'])->report('1', recipients()))->toThrow(GatewayUnavailableException::class);
});
