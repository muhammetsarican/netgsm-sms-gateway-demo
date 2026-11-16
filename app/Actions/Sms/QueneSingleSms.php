<?php

declare(strict_types=1);

namespace App\Actions\Sms;

use App\Jobs\SendSmsMessage;
use App\Models\SmsCampaign;
use App\Models\SmsMessage;
use App\Sms\Data\PhoneNumber;
use App\Sms\Enums\CampaignType;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final class QueneSingleSms{
  public function handle(PhoneNumber $phone, string $body, ?string $header, string $idempotencyKey):SmsMessage{
    $key=hash('sha256', $idempotencyKey.'|'.$phone->value);

    $existing=SmsMessage::query()->where('idempotency_key', $key)->first();

    if($existing!==null){
      return $existing;
    }

    try{
      $message=DB::transaction(function () use ($phone, $body, $header, $key):SmsMessage{
        $campaign=SmsCampaign::create([
          'type'=>CampaignType::Single,
          'body'=>$body,
          'header'=>$header,
          'total_receipients'=>1,
        ]);

        return $campaign->messages()->create([
          'phone'=>$phone->value,
          'idempotency_key'=>$key,
        ]);
      });
    }catch(UniqueConstraintViolationException){
      return SmsMessage::query()->where('idempotency_key',$key)->firstOrFail();
    }

    SendSmsMessage::dispatch($message->id);

    return $message;
  }
}