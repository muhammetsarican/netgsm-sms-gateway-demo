<?php

use App\Jobs\SendSmsMessage;
use App\Models\SmsMessage;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

beforeEach(function () {
    Queue::fake();
});

it('SMS isteğini kuyruğa alır ve 202 döner', function () {
    /** @var TestCase $this */
    $response = $this->postJson('/api/sms', [
        'phone' => '0532 123 45 67',
        'message' => 'Merhaba',
    ], ['Idempotency-Key' => 'istek-1']);

    $response->assertStatus(202)->assertHeader('Idempotent-Replayed', 'false')->assertJsonPath('data.status', 'pending')->assertJsonPath('data.phone', '+90 532 *** ** 67');

    expect(SmsMessage::count())->toBe(1);
    Queue::assertPushed(SendSmsMessage::class, 1);
});

it('aynı Idempotency-Key ile gelen ikinci istek yeni mesaj oluşturmaz', function () {
    /** @var TestCase $this */
    $payload = ['phone' => '05321234567', 'message' => 'Merhaba'];

    $first = $this->postJson('/api/sms', $payload, ['Idempotency-Key' => 'istek-1']);
    $second = $this->postJson('/api/sms', $payload, ['Idempotency-Key' => 'istek-1']);

    $second->assertStatus(202)->assertHeader('Idempotent-Replayed', 'true')->assertJsonPath('data.id', $first->json('data.id'));
});

it('geçersiz numarayı 422 ile reddeder', function () {
    /** @var TestCase $this */
    $this->postJson('/api/sms', ['phone' => '0312 123 45 67', 'message' => 'Merhaba'])->assertStatus(422)->assertJsonValidationErrors('phone');

    Queue::assertNothingPushed();
});

it('aynı numaraya dakikada 3 istekten fazlasına izin vermez', function () {
    /** @var TestCase $this */
    foreach (range(1, 3) as $i) {
        $this->postJson('/api/sms', ['phone' => '05321234567', 'message' => 'Merhaba'], ['Idempotency-Key' => "istek-{$i}"])->assertStatus(202);
    }

    $this->postJson('/api/sms', ['phone' => '05321234567', 'message' => 'Merhaba'], ['Idempotency-Key' => 'istek-4'])->assertStatus(429);
});

it('mesaj durumunu sorgulamaya izin verir', function () {
    /** @var TestCase $this */
    $id = $this->postJson('/api/sms', ['phone' => '05321234567', 'message' => 'Merhaba'])->json('data.id');

    $this->getJson("/api/sms/{$id}")->assertOk()->assertJsonPath('data.id', $id)->assertJsonPath('data.status_label', 'Kuyrukta');
});
