<?php

use App\Jobs\SendSmsMessage;
use App\Models\SmsCampaign;
use App\Models\SmsMessage;
use App\Sms\Contracts\SmsGateway;
use App\Sms\Data\OutgoingSms;
use App\Sms\Data\SendResult;
use App\Sms\Enums\MessageStatus;
use App\Sms\Exceptions\GatewayUnavailableException;
use Illuminate\Support\Str;

function pendingMessage():SmsMessage{
  $campaign=SmsCampaign::create(['type'=>'single','body'=>'Merhaba', 'total_recipients'=>1]);

  return $campaign->messages()->create([
    'phone'=>'905321234567',
    'idempotency_key'=>Str::random(64)
  ]);
}

function gatewayReturning(SendResult|Throwable $outcome):SmsGateway{
  return new class($outcome) implements SmsGateway{
    public int $calls=0;

    public function __construct(private SendResult|Throwable $outcome)
    {
    }

    public function send(OutgoingSms $sms):SendResult{
      $this->calls++;

      if($this->outcome instanceof Throwable){
        throw $this->outcome;
      }

      return $this->outcome;
    }

    public function name():string{
      return 'stub';
    }
  };
}

it('kabul edilen mesajı sent olarak işaretler', function (){
  $message = pendingMessage();

  (new SendSmsMessage($message->id))->handle(gatewayReturning(SendResult::accepted('1311033503')));

  $message->refresh();

  expect($message->status)->toBe(MessageStatus::Sent)->and($message->provider_reference)->toBe('1311033503')->and($message->attempts)->toBe(1)->and($message->sent_at)->not->toBeNull();
});

it('kalıcı reddi failed olarak işaretler', function (){
  $message=pendingMessage();

  (new SendSmsMessage($message->id))->handle(gatewayReturning(SendResult::rejected('40', 'Başlık tanımsız')));

  expect($message->refresh()->status)->toBe(MessageStatus::Failed)->and($message->error_code)->toBe('40');
});

it('geçici reddi tekrar denemek için pending durumuna geri alır', function (){
  $message=pendingMessage();

  (new SendSmsMessage($message->id))->handle(gatewayReturning(SendResult::rejected('80','Sınır aşımı',retryable:true)));

  expect($message->refresh()->status)->toBe(MessageStatus::Pending)->and($message->error_code)->toBe('80');
});

it('belirsiz durumda tekrar denemez, unknown olarak işaretler', function (){
  $message=pendingMessage();
  $gateway=gatewayReturning(GatewayUnavailableException::unexpectedResponse('stub', 'timeout'));

  (new SendSmsMessage($message->id))->handle($gateway);

  expect($message->refresh()->status)->toBe(MessageStatus::Unknown)->and($gateway->calls)->toBe(1);
});

it('aynı job iki kez çalışsa bile SMS bir kez gönderilir',function(){
  $message=pendingMessage();
  $gateway=gatewayReturning(SendResult::accepted('1'));

  (new SendSmsMessage($message->id))->handle($gateway);
  (new SendSmsMessage($message->id))->handle($gateway);

  expect($gateway->calls)->toBe(1)->and($message->refresh()->attempts)->toBe(1);
});