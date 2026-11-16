<?php

declare(strict_types=1);

namespace App\Sms\Enums;

enum MessageStatus: string
{
    case Pending = 'pending';
    case Sending = 'sending';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Unknown = 'unknown';

    public function isFinal(): bool
    {
        return match ($this) {
            self::Delivered, self::Failed => true,
            default => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Kuyrukta',
            self::Sending => 'Gönderiliyor',
            self::Sent => 'Operatöre iletildi',
            self::Delivered => 'Teslim edildi',
            self::Failed => 'Başarısız',
            self::Unknown => 'Belirsiz (rapor bekleniyor)',
        };
    }
}
