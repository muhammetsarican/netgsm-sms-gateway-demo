<?php

return [
    'driver' => env('SMS_DRIVER', 'fake'),
    'default_header' => env('SMS_HEADER', 'DEMO'),
    'log_channel' => env('SMS_LOG_CHANNEL', 'sms'),

    'netgsm' => [
        'usercode' => env('NETGSM_USERCODE'),
        'password' => env('NETGSM_PASSWORD'),
        'header' => env('NETGSM_HEADER'),
    ],
];
