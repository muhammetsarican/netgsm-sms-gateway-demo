<?php

declare(strict_types=1);

namespace App\Sms\Data;

final readonly class SendResult
{
    private function __construct(
        public bool $accepted,
        public ?string $reference = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public bool $retryable=false,
    ) {}

    public static function accepted(string $reference): self
    {
        return new self(accepted: true, reference: $reference);
    }

    public static function rejected(string $errorCode, string $errorMessage, bool $retryable=false): self
    {
        return new self(accepted: false, errorCode: $errorCode, errorMessage: $errorMessage, retryable: $retryable);
    }
}
