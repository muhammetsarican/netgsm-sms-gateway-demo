<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\SmsMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SmsMessage */
class SmsMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var SmsMessage $message */
        $message = $this->resource;

        return [
            'id' => $message->id,
            'status' => $message->status->value,
            'status_label' => $message->status->label(),
            'phone' => $message->phoneNumber()->masked(),
            'provider_reference' => $message->provider_reference,
            'error_code' => $message->error_code,
            'attempts' => $message->attempts,
            'sent_at' => $message->sent_at?->toIso8601String(),
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }
}
