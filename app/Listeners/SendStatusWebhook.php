<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SmsStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendStatusWebhook implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 6;

    /** @var list<int> */
    public array $backoff = [10, 60, 300, 900, 3600];

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SmsStatusChanged $event): void
    {
        //
        $message = SmsMessage::query()->with('campaign')->find($event->messageId);
        $url = $message?->campaign->callback_url;

        if ($message === null || $url === null) {
            return;
        }

        $secret = (string) config('sms.webhook_secret');

        if ($secret === '') {
            throw new LogicException('SMS_WEBHOOK_SECRET tanımlı değil');
        }

        $body = json_encode([
            'id' => $event->id,
            'type' => 'sms.status_changed',
            'occurred_at' => $event->occurredAt->toIso8601String(),
            'data' => [
                'message_id' => $message->id,
                'status' => $event->status->value,
                'error_code' => $message->error_code,
                'provider_reference' => $message->provider_reference,
            ],
        ], JSON_THROW_ON_ERROR);

        $timestamp = (string) $event->occuredAt->getTimestamp();
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, $secret);

        Http::timeout(5)
            ->withHeaders([
                'X-Sms-Event-Id' => $event->id,
                'X-Sms-Timestamp' => $timestamp,
                'X-Sms-Signature' => 'sha256='.$signature,
            ])->withBody($body, 'application/json')->post($url)->throw();
    }
}
