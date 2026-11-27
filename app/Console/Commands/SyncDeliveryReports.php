<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Events\SmsStatusChanged;
use App\Models\SmsMessage;
use App\Sms\Contracts\DeliveryReportProvider;
use App\Sms\Data\DeliveryReport;
use App\Sms\Data\PhoneNumber;
use App\Sms\Enums\MessageStatus;
use App\Sms\Exceptions\GatewayUnavailableException;
use Illuminate\Console\Command;

final class SyncDeliveryReports extends Command
{
    protected $signature = 'sms:sync-reports {--limit=100 : Bir çalıştırmada sorgulanacak en fazla gönderim sayısı}';

    protected $description = 'Gönderilmiş mesajların teslim raporlarını sorgulayıp durumlarını günceller';

    public function handle(DeliveryReportProvider $provider): int
    {

        $references = SmsMessage::query()
            ->where('status', MessageStatus::Sent->value)
            ->whereNotNull('provider_reference')
            ->whereBetween('sent_at', [now()->subDays(3), now()->subMinute()])
            ->distinct()
            ->limit((int) $this->option('limit'))
            ->pluck('provider_reference');

        $updated = 0;

        foreach ($references as $reference) {
            $recipients = SmsMessage::query()
                ->where('provider_reference', $reference)
                ->where('status', MessageStatus::Sent->value)
                ->pluck('phone')
                ->map(fn (string $phone) => PhoneNumber::from($phone))
                ->values()
                ->all();

            /** @var list<PhoneNumber> $recipients */
            $recipients = array_values($recipients);

            try {
                /** @var iterable<DeliveryReport> $reports */
                $reports = $provider->report((string) $reference, $recipients);
            } catch (GatewayUnavailableException $e) {
                $this->warn("{$reference}: rapor alınamadı ({$e->getMessage()})");

                continue;
            }

            foreach ($reports as $report) {
                if ($report->status === MessageStatus::Sent) {
                    continue;
                }

                $message = SmsMessage::query()
                    ->where('provider_reference', $reference)
                    ->where('phone', $report->phone->value)
                    ->where('status', MessageStatus::Sent->value)
                    ->first();

                if ($message === null) {
                    continue;
                }

                $affected = SmsMessage::query()
                    ->whereKey($message->id)
                    ->where('status', MessageStatus::Sent->value)
                    ->update([
                        'status' => $report->status->value,
                        'error_code' => $report->errorCode,
                        'delivered_at' => $report->status === MessageStatus::Delivered ? ($report->reportedAt ?? now()) : null,
                    ]);

                if ($affected === 1) {
                    SmsStatusChanged::dispatch($message->id, $report->status);
                    $updated++;
                }
            }
        }

        $this->info("{$references->count()} gönderim sorgulandı, {$updated} mesaj güncellendi");

        return self::SUCCESS;
    }
}
