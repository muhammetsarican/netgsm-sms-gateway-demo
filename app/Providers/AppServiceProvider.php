<?php

namespace App\Providers;

use App\Sms\Contracts\SmsGateway;
use App\Sms\Data\PhoneNumber;
use App\Sms\Gateways\FakeGateway;
use App\Sms\Gateways\Netgsm\NetgsmGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Netgsm\Sms\SmsSend;
use Psr\Log\LoggerInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->singleton(SmsGateway::class, function (Application $app): SmsGateway {
            $logger = $app->make('log')->channel(config('sms.log_channel'));

            return match (config('sms.driver')) {
                'fake' => new FakeGateway($logger),
                'netgsm' => $this->makeNetgsmGateway($logger),
                default => throw new InvalidArgumentException(
                    'Bilinmeyen SMS driver: '.config('sms.driver')
                )
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        RateLimiter::for('sms-send', function (Request $request) {
            $phone = PhoneNumber::tryFrom((string) $request->input('phone'))->value ?? 'invalid';

            return [
                Limit::perMinute(30)->by('ip:'.$request->ip()),
                Limit::perMinute(3)->by('phone:'.$phone),
            ];
        });
    }

    private function makeNetgsmGateway(LoggerInterface $logger): NetgsmGateway
    {
        $config = config('sms.netgsm');

        foreach (['usercode', 'password', 'header'] as $key) {
            if (blank($config[$key] ?? null)) {
                throw new InvalidArgumentException("Netgsm yapılandırması eksik: sms.netgsm.{$key}");
            }
        }

        return new NetgsmGateway(
            client: new SmsSend,
            logger: $logger,
            defaultSender: $config['header'],
            iysFilter: (string) $config['iys_filter'],
        );
    }
}
