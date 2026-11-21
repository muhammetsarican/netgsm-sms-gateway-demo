<?php

use App\Models\SmsCampaign;
use App\Models\SmsMessage;
use App\Sms\Contracts\DeliveryReportProvider;
use App\Sms\Data\DeliveryReport;
use App\Sms\Data\PhoneNumber;
use App\Sms\Enums\MessageStatus;
use Illuminate\Support\Str;

function sentMessage(string $phone, string $reference, int $minutesAgo = 5): SmsMessage
{
    $campaign = SmsCampaign::create(['type' => 'single', 'body' => 'Merhaba', 'total_recipients' => 1]);

    $message = $campaign->messages()->create([
        'phone' => $phone,
        'idempotency_key' => Str::random(64),
    ]);

    $message->update([
        'status' => MessageStatus::Sent,
        'provider_reference' => $reference,
        'sent_at' => now()->subMinutes($minutesAgo),
    ]);

    return $message;
}

/**
 * @param  list<DeliveryReport>  $reports
 */
function fakeReports(array $reports): void
{
    app()->instance(DeliveryReportProvider::class, new class($reports) implements DeliveryReportProvider
    {
        public int $calls = 0;

        public function __construct(private array $reports) {}

        public function report(string $reference, array $recipients): array
        {
            $this->calls++;

            return $this->reports;
        }
    });
}

it('teslim raporuna göre mesajları delivered ve failed yapar', function () {
    $delivered = sentMessage('905321234567', 'B1');
    $failed = sentMessage('905331234567', 'B1');

    fakeReports([
        new DeliveryReport(PhoneNumber::from('905321234567'), MessageStatus::Delivered),
        new DeliveryReport(PhoneNumber::from('905331234567'), MessageStatus::Failed, errorCode: '3'),
    ]);

    $this->artisan('sms:sync-reports')->assertSuccessful();

    expect($delivered->refresh()->status)->toBe(MessageStatus::Delivered)->and($delivered->delivered_at)->not->toBeNull()->and($failed->refresh()->status)->toBe(MessageStatus::Failed)->and($failed->error_code)->toBe('3');
});

it('henüz bekleyen raporlarda mesajı sent olarak bırakır', function () {
    $message = sentMessage('905321234567', 'B1');

    fakeReports([new DeliveryReport(PhoneNumber::from('905321234567'), MessageStatus::Sent)]);

    $this->artisan('sms:sync-reports')->assertSuccessful();

    expect($message->refresh()->status)->toBe(MessageStatus::Sent);
});

it('yeni gönderilmiş mesajları henüz sorgulamaz', function () {
    $message = sentMessage('905321234567', 'B1', 0);

    fakeReports([new DeliveryReport(PhoneNumber::from('905321234567'), MessageStatus::Delivered)]);

    $this->artisan('sms:sync-reports')->assertSuccessful();

    expect($message->refresh()->status)->toBe(MessageStatus::Sent);
});
