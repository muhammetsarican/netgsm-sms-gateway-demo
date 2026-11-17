<?php

declare(strict_types=1);

namespace App\Sms\Enums;

enum CampaignType: string
{
    //
    case Single = 'single';
    case Bulk = 'bulk';
}
