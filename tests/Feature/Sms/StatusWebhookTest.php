<?php

use App\Events\SmsStatusChanged;
use App\Jobs\SendSmsMessage;
use App\Listeners\SendStatusWebhook;
use App\Models\SmsCampaign;
use App\Models\SmsMessage;
use App\Sms\Contracts\SmsGateway;
use App\Sms\Data\OutgoingSms;
use App\Sms\Data\SendResult;
use App\Sms\Enums\MessageStatus;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    config(['sms.webhook_secret' => 'test-secret']);
});

function messageWithCallback(?string $callbackUrl): SmsMessage
{
    $campaign = SmsCampaign::create([
        'type' => 'single',
        'body' => 'Merhaba',
        'total_recipients' => 1,
        'callback_url' => $callbackUrl,
    ]);

    return $campaign->messages()->create([
        'phone' => '905321234567',
        'idempotency_key' => Str::random(64),
    ]);
}

it('durum değişince istemciye imzalı webhook gönderir', function () {
    Http::fake();
    $message = messageWithCallback('https://magaza.test/sms-durum');
    $event = new SmsStatusChanged($message->id, MessageStatus::Delivered);

    (new SendStatusWebhook)->handle($event);

    Http::assertSent(function (Request $request) use ($message, $event) {
        $timestamp = $request->header('X-Sms-Timestamp')[0];
        $expected = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$request->body(), 'test-secret');
        $payload = json_decode($request->body(), true);

        return $request->url() === 'https://magaza.test/sms-durum'
            && $request->header('X-Sms-Signature')[0] === $expected
            && $request->header('X-Sms-Event-Id')[0] === $event->id
            && $payload['data']['message_id'] === $message->id
            && $payload['data']['status'] === 'delivered';
    });
});

it('callback_url yoksa hiçbir istek göndermez', function () {
    Http::fake();
    $message = messageWithCallback(null);

    (new SendStatusWebhook)->handle(new SmsStatusChanged($message->id, MessageStatus::Sent));

    Http::assertNothingSent();
});

it('istemci 2xx dönmezse exception fırlatır, böylece kuyruk tekrar dener', function () {
    Http::fake(['*' => Http::response(null, 500)]);
    $message = messageWithCallback('https://magaza.test/sms-durum');

    expect(fn () => (new SendStatusWebhook)->handle(new SmsStatusChanged($message->id, MessageStatus::Sent)))
        ->toThrow(RequestException::class);
});

it('SMS gönderim job\'u durum değişikliği olayı yayınlar', function () {
    Event::fake([SmsStatusChanged::class]);
    $message = messageWithCallback('https://magaza.test/sms-durum');

    $gateway = new class implements SmsGateway
    {
        public function send(OutgoingSms $sms): SendResult
        {
            return SendResult::accepted('1311033503');
        }

        public function name(): string
        {
            return 'stub';
        }
    };

    (new SendSmsMessage($message->id))->handle($gateway);

    Event::assertDispatched(
        SmsStatusChanged::class,
        fn (SmsStatusChanged $event) => $event->messageId === $message->id && $event->status === MessageStatus::Sent,
    );
});

it('API sadece https callback adreslerini kabul eder', function () {
    $this->postJson('/api/sms', [
        'phone' => '05321234567',
        'message' => 'Merhaba',
        'callback_url' => 'http://169.254.169.254/latest/meta-data',
    ])->assertStatus(422)->assertJsonValidationErrors('callback_url');
});
