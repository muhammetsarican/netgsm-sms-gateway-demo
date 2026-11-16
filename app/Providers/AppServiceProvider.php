<?php

namespace App\Providers;

use App\Sms\Contracts\SmsGateway;
use App\Sms\Gateways\FakeGateway;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Netgsm\Sms\SmsSend;
use Psr\Log\LoggerInterface;
use App\Sms\Gateways\Netgsm\NetgsmGateway;
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
            defaultHeader: $config['header'],
            iysFilter: (string) $config['iys_filter'],
        );
    }
}
