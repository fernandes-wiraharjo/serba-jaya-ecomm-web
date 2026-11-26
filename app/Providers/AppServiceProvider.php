<?php

namespace App\Providers;

use App\Docs\ApiIntroduction;
use App\Docs\RouteDocumentOrder;
use App\Support\GoogleIdentityProvider;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Apple\Provider as AppleProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(function () {
            $rule = Password::min(8)
                ->mixedCase()
                ->numbers();

            return $rule;
        });

        Scramble::configure()->withDocumentTransformers(function (OpenApi $openApi): void {
            $openApi->info->setDescription(ApiIntroduction::markdown());
            RouteDocumentOrder::apply($openApi);
        });

        Event::listen(function (SocialiteWasCalled $event): void {
            $event->extendSocialite('apple', AppleProvider::class);
        });

        Socialite::extend('google', function ($app) {
            return Socialite::buildProvider(
                GoogleIdentityProvider::class,
                $app['config']['services.google'],
            );
        });
    }
}
