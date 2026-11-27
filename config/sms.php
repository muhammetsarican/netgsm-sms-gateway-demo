<?php

return [
    'driver' => env('SMS_DRIVER', 'fake'),
    'log_channel' => env('SMS_LOG_CHANNEL', 'sms'),

    'netgsm' => [
        'usercode' => env('NETGSM_USERCODE'),
        'password' => env('NETGSM_PASSWORD'),
        'header' => env('NETGSM_HEADER'),
        'iys_filter' => env('NETGSM_IYS_FILTER', '0'),
        'webhook_secret' => env('SMS_WEBHOOK_SECRET'),
    ],
];
