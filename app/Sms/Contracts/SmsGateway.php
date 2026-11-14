<?php

declare(strict_types=1);

namespace App\Sms\Contracts;

use App\Sms\Data\OutgoingSms;
use App\Sms\Data\SendResult;

interface SmsGateway
{
    public function send(OutgoingSms $sms): SendResult;

    public function name(): string;
}
