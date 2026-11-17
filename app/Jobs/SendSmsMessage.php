<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\SmsMessage;
use App\Sms\Contracts\SmsGateway;
use App\Sms\Data\OutgoingSms;
use App\Sms\Enums\MessageStatus;
use App\Sms\Exceptions\GatewayUnavailableException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class SendSmsMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 30, 60, 120];

    public function __construct(public readonly int $messageId) {}

    /**
     * Execute the job.
     */
    public function handle(SmsGateway $gateway): void
    {
        //
        $claimed = SmsMessage::query()->whereKey($this->messageId)->where('status', MessageStatus::Pending->value)->update([
            'status' => MessageStatus::Sending->value,
            'attempts' => DB::raw('attempts+1'),
        ]);

        if ($claimed === 0) {
            return;
        }

        $message = SmsMessage::query()->with('campaign')->findOrFail($this->messageId);

        try {
            $result = $gateway->send(new OutgoingSms(
                recipients: [$message->phoneNumber()],
                body: $message->campaign->body,
                header: $message->campaign->header,
            ));
        } catch (GatewayUnavailableException $e) {
            $message->update([
                'status' => MessageStatus::Unknown,
                'error_message' => $e->getMessage(),
            ]);

            return;
        }

        if ($result->accepted) {
            $message->update([
                'status' => MessageStatus::Sent,
                'provider_reference' => $result->reference,
                'error_code' => null,
                'error_message' => null,
                'sent_at' => now(),
            ]);

            return;
        }

        if ($result->retryable && $this->attempts() < $this->tries) {
            $message->update([
                'status' => MessageStatus::Pending,
                'error_code' => $result->errorCode,
                'error_message' => $result->errorMessage,
            ]);

            $this->release($this->backoff[$this->attempts() - 1] ?? 120);

            return;
        }

        $message->update([
            'status' => MessageStatus::Failed,
            'error_code' => $result->errorCode,
            'error_message' => $result->errorMessage,
        ]);
    }
}
