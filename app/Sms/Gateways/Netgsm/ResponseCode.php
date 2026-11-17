<?php

declare(strict_types=1);

namespace App\Sms\Gateways\Netgsm;

enum ResponseCode: string
{
    case Success = '00';
    case SuccessStartDateAdjusted = '01';
    case SuccessStopDateAdjusted = '02';
    case InvalidMessage = '20';
    case AuthenticationFailed = '30';
    case HeaderNotDefined = '40';
    case IysNotAllowed = '50';
    case IysBrandMissing = '51';
    case InvalidParameters = '70';
    case SendLimitExceeded = '80';
    case DuplicateLimitExceeded = '85';

    public function isSuccess(): bool
    {
        return match ($this) {
            self::Success, self::SuccessStartDateAdjusted, self::SuccessStopDateAdjusted => true,
            default => false,
        };
    }

    public function isRetryable(): bool
    {
        return match ($this) {
            self::SendLimitExceeded, self::DuplicateLimitExceeded => true,
            default => false,
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Success => 'Mesaj Netgsm sistemine ulaştı.',
            self::SuccessStartDateAdjusted => 'Başlangıç tarihi hatalıydı, sistem tarihiyle gönderildi.',
            self::SuccessStopDateAdjusted => 'Bitiş tarihi hatalıydı, sistem tarafından düzeltildi.',
            self::InvalidMessage => 'Mesaj metni hatalı veya izin verilen uzunluğu aşıyor.',
            self::AuthenticationFailed => 'Geçersiz kullanıcı bilgisi, API izni yok veya IP kısıtı var.',
            self::HeaderNotDefined => 'Mesaj başlığı (gönderici adı) sistemde tanımlı değil.',
            self::IysNotAllowed => 'Hesap İYS kontrollü gönderime uygun değil.',
            self::IysBrandMissing => 'Hesaba tanımlı İYS marka kodu yok.',
            self::InvalidParameters => 'Hatalı sorgu: parametrelerden biri hatalı veya eksik.',
            self::SendLimitExceeded => 'Gönderim sınır aşımı.',
            self::DuplicateLimitExceeded => 'Aynı numaraya kısa sürede çok fazla mesaj gönderildi.',
        };
    }
}
