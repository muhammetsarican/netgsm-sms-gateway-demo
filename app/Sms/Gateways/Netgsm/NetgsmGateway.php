<?php

declare(strict_types=1);

namespace App\Sms\Gateways\Netgsm;

use App\Sms\Contracts\DeliveryReportProvider;
use App\Sms\Contracts\SmsGateway;
use App\Sms\Data\DeliveryReport;
use App\Sms\Data\OutgoingSms;
use App\Sms\Data\PhoneNumber;
use App\Sms\Data\SendResult;
use App\Sms\Enums\MessageStatus;
use App\Sms\Exceptions\GatewayUnavailableException;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Netgsm\Sms\SmsSend;
use Psr\Log\LoggerInterface;
use Throwable;

final class NetgsmGateway implements DeliveryReportProvider, SmsGateway
{
    public function __construct(private readonly SmsSend $client, private readonly LoggerInterface $logger, private readonly string $defaultSender, private readonly string $iysFilter = '0') {}

    public function send(OutgoingSms $sms): SendResult
    {
        $payload = [
            'message' => $sms->body,
            'no' => array_map(fn (PhoneNumber $phone) => $phone->national(), $sms->recipients),
            'header' => $sms->sender ?? $this->defaultSender,
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

    public function report(string $reference, array $recipients): array
    {
        try {
            $response = $this->client->smsSorgulama([
                'bulkid' => $reference,
                'type' => 0,
                'status' => 100,
                'bastar' => '',
                'bittar' => '',
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Netgsm rapor sorgusu tamamlanamadı', ['exception' => $e::class]);

            throw GatewayUnavailableException::transportFailed($this->name(), $e);
        }

        if (! is_array($response)) {
            throw GatewayUnavailableException::unexpectedResponse($this->name(), 'rapor yanıtı dizi değil');
        }

        if (isset($response['code'])) {
            if ((string) $response['code'] === '60') {
                return [];
            }

            throw GatewayUnavailableException::unexpectedResponse($this->name(), "rapor kodu: '{$response['code']}'");
        }

        $records = isset($response['durumcode']) ? [$response] : $response;

        $reports = [];

        foreach ($records as $record) {
            if (! is_array($record) || ! isset($record['durumcode'])) {
                continue;
            }

            $phone = PhoneNumber::tryFrom((string) ($record['cepno'] ?? ''));

            if ($phone === null) {
                continue;
            }

            $reports[] = $this->toDeliveryReport($phone, (int) $record['durumcode'], $record['tarih'] ?? null);
        }

        return $reports;
    }

    public function name(): string
    {
        return 'netgsm';
    }

    private function toDeliveryReport(PhoneNumber $phone, int $code, mixed $date): DeliveryReport
    {
        return match ($code) {
            0 => new DeliveryReport($phone, MessageStatus::Sent),
            1 => new DeliveryReport($phone, MessageStatus::Delivered, reportedAt: self::parseDate($date)),
            default => new DeliveryReport($phone, MessageStatus::Failed, errorCode: (string) $code),
        };
    }

    private function parseDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('d.m.Y H:i:s', $value, new DateTimeZone('Europe/Istanbul')) ?: null;
        } catch (Throwable) {
            return null;
        }
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
