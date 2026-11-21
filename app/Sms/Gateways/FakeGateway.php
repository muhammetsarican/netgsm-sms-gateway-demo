<?php

declare(strict_types=1);

namespace App\Sms\Gateways;

use App\Sms\Contracts\SmsGateway;
use App\Sms\Data\DeliveryReport;
use App\Sms\Data\OutgoingSms;
use App\Sms\Data\PhoneNumber;
use App\Sms\Data\SendResult;
use App\Sms\Enums\MessageStatus;
use Carbon\CarbonImmutable;
use Psr\Log\LoggerInterface;

final class FakeGateway implements SmsGateway
{
    /**
     * @var list<OutgoingSms>
     */
    private array $sent = [];

    public function __construct(private readonly LoggerInterface $logger) {}

    public function send(OutgoingSms $sms): SendResult
    {
        $this->sent[] = $sms;

        $reference = 'fake-'.bin2hex(random_bytes(6));

        $this->logger->info('SMS gönderildi (fake)', [
            'reference' => $reference,
            'recipients' => array_map(fn (PhoneNumber $phone) => $phone->masked(), $sms->recipients),
            'length' => mb_strlen($sms->body),
        ]);

        return SendResult::accepted($reference);
    }

    /**@return list<OutgoingSms>  */
    public function sent(): array
    {
        return $this->sent;
    }

    public function report(string $reference, array $receipients): array
    {
        return array_map(
            fn (PhoneNumber $phone) => new DeliveryReport($phone, MessageStatus::Delivered, reportedAt: CarbonImmutable::now()),
            $receipients,
        );
    }

    public function name(): string
    {
        return 'fake';
    }
}
