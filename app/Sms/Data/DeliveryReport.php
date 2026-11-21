<?php

declare(strict_types=1);

namespace App\Sms\Data;

use App\Sms\Enums\MessageStatus;
use Carbon\CarbonImmutable;

final readonly class DeliveryReport
{
    /** @param MessageStatus $status Sent, Delivered, Failed  */
    public function __construct(
        public PhoneNumber $phone,
        public MessageStatus $status,
        public ?string $errorCode = null,
        public ?CarbonImmutable $reportedAt = null,
    ) {}
}
