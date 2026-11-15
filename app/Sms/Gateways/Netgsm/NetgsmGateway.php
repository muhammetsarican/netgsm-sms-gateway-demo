<?php

declare(strict_types=1);

namespace App\Sms\Gateways\Netgsm;

use App\Sms\Contracts\SmsGateway;
use App\Sms\Data\OutgoingSms;
use App\Sms\Data\PhoneNumber;
use App\Sms\Data\SendResult;
use App\Sms\Exceptions\GatewayUnavailableException;
use Netgsm\Sms\SmsSend;
use Psr\Log\LoggerInterface;
use Throwable;

final class NetgsmGateway implements SmsGateway
{
    public function __construct(private readonly SmsSend $client, private readonly LoggerInterface $logger, private readonly string $defaultHeader, private readonly string $iysFilter = '0') {}

    public function send(OutgoingSms $sms): SendResult
    {
        $payload = [
            'message' => $sms->body,
            'no' => array_map(fn (PhoneNumber $phone) => $phone->national(), $sms->recipients),
            'header' => $sms->header ?? $this->defaultHeader,
            'filter' => $this->iysFilter,
            'encoding' => self::needsTurkishEncoding($sms->body) ? 'tr' : null,
            'startdate' => '',
            'stopdate' => '',
        ];

        try {
            $response = $this->client->smsGonder($payload);
        } catch (Throwable $e) {
            $this->logger->error('Netgsm isteği tamamlanamadı', ['exception' => $e::class]);

            throw GatewayUnavailableException::transportFailed($this->name(), $e);
        }

        return $this->toResult($response, $sms);
    }

    public function name(): string
    {
        return 'netgsm';
    }

    private function toResult(mixed $response, OutgoingSms $sms): SendResult
    {
        $rawCode = is_array($response) ? (string) ($response['code'] ?? '') : '';
        $code = ResponseCode::tryFrom($rawCode);

        if ($code === null) {
            $this->logger->error('Netgsm tanınmayan yanıt döndü', ['code' => $rawCode]);

            throw GatewayUnavailableException::unexpectedResponse($this->name(), "kod: '{$rawCode}'");
        }

        if ($code->isSuccess()) {
            $bulkId = (string) ($response['bulkid'] ?? '');

            if ($bulkId === '') {
                throw GatewayUnavailableException::unexpectedResponse($this->name(), 'başarılı yanıtta bulkid yok');
            }

            $this->logger->info('SMS Netgsm tarafından kabul edildi', [
                'bulkid' => $bulkId,
                'code' => $code->value,
                'recipients' => array_map(fn (PhoneNumber $phone) => $phone->masked(), $sms->recipients),
            ]);
               
            return SendResult::accepted($bulkId);
        }

        $this->logger->warning('SMS Netgsm tarafından reddedildi', [
            'code' => $code->value,
            'reason' => $code->description(),
        ]);


            return SendResult::rejected($code->value, $code->description(), $code->isRetryable());
        }

    private static function needsTurkishEncoding(string $text): bool
    {
        return preg_match('/[çğıöşüÇĞİÖŞÜ]/u', $text) === 1;
    }
}
