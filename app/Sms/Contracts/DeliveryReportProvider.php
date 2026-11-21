<?php

declare(strict_types=1);

namespace App\Sms\Contracts;

use App\Sms\Data\DeliveryReport;
use App\Sms\Data\PhoneNumber;

interface DeliveryReportProvider
{
    /**
     * @param  list<PhoneNumber>  $recipients
     * @return list<DeliveryReport>
     */
    public function report(string $reference, array $recipients): array;
}
