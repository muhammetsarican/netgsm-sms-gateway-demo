<?php

declare(strict_types=1);

namespace App\Sms\Exceptions;

use RuntimeException;
use Throwable;

final class GatewayUnavailableException extends RuntimeException
{
    public static function transportFailed(string $gateway, Throwable $previous): self
    {
        return new self("{$gateway}: istek tamamlanamadı (".$previous::class.')', previous: $previous);
    }

    public static function unexpectedResponse(string $gateway, string $detail): self
    {
        return new self("{$gateway}: beklenmeyen yanıt ({$detail})");
    }
}
