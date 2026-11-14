<?php

namespace App\Providers;

use App\Sms\Contracts\SmsGateway;
use App\Sms\Gateways\FakeGateway;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->singleton(SmsGateway::class, function (Application $app): SmsGateway {
            $logger = $app['log']->channel(config('sms.log_channel'));

            return match (config('sms.driver')) {
                'fake' => new FakeGateway($logger),
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
}
