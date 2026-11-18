<?php

declare(strict_types=1);

namespace App\Sms\Data;

use InvalidArgumentException;

final readonly class OutgoingSms
{
    /**
     * @param  list<PhoneNumber>  $recipients;
     */
    public function __construct(
        public array $recipients,
        public string $body,
        public ?string $sender = null,
    ) {
        if ($recipients === []) {
            throw new InvalidArgumentException('En az bir alıcı gerekli');
        }

        if (trim($body) === '') {
            throw new InvalidArgumentException('Mesaj metni boş olamaz');
        }
    }

    public static function single(PhoneNumber|string $to, string $body, ?string $sender = null): self
    {
        $phone = $to instanceof PhoneNumber ? $to : PhoneNumber::from($to);

        return new self([$phone], $body, $sender);
    }
}
