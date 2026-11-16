<?php

namespace App\Models;

use App\Sms\Enums\CampaignType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsCampaign extends Model
{
    //
    protected $fillable = [
        'type',
        'body',
        'header',
        'total_recipients',
    ];

    protected function casts():array{
        return [
            'type'=>CampaignType::class,
            'total_recipients'=>'integer',
        ];
    }

    /** @return HasMany<SmsMessage, $this>  */
    public function messages():HasMany{
        return $this->hasMany(SmsMessage::class);
    }
}
