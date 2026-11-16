<?php

namespace App\Models;

use App\Sms\Data\PhoneNumber;
use App\Sms\Enums\MessageStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsMessage extends Model
{
    //
    protected $fillable = [
        'sms_campaign_id',
        'phone',
        'status',
        'idempotency_key',
        'provider_reference',
        'error_code',
        'error_message',
        'attempts',
        'sent_at',
        'delivered_at',
    ];

    protected $attributes=[
        'status'=>MessageStatus::Pending->value,
        'attempts'=>0,
    ];

    protected function casts():array{
        return [
            'status'=>MessageStatus::class,
            'attempts'=>'integer',
            'sent_at'=>'datetime',
            'delivered_at'=>'datetime',
        ];
    }

    /** @return BelongsTo<SmsCampaign, $this>  */
    public function campaign():BelongsTo{
        return $this->belongsTo(SmsCampaign::class, 'sms_campaign_id');
    }

    public function phoneNumber():PhoneNumber{
        return PhoneNumber::from($this->phone);
    }
}
