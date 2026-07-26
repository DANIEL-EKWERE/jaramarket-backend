<?php

namespace App\Providers;

use App\Channels\FirebaseChannel;
use App\Services\Firebase\FirebaseNotificationService;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Contract\Messaging;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FirebaseNotificationService::class, function ($app) {
            return new FirebaseNotificationService($app->make(Messaging::class));
        });

        $this->app->singleton(
            \Illuminate\Contracts\Console\Kernel::class,
            \App\Console\Kernel::class
        );
    }

    public function boot(): void
    {
        // Dynamically reconfigure S3 from DB settings if storage_disk = s3
        \App\Http\Controllers\SettingsController::reconfigureS3();

        // Register firebase as a notification channel
        Notification::resolved(function (ChannelManager $service) {
            $service->extend('firebase', function ($app) {
                return new FirebaseChannel($app->make(FirebaseNotificationService::class));
            });
        });

        // API response macros (duplicated from MacroServiceProvider as fallback)
        if (!Response::hasMacro('success')) {
            Response::macro('success', function ($message, $data = [], $status = 200) {
                return Response::json(['status' => true, 'message' => $message, 'data' => $data], $status);
            });
        }
        if (!Response::hasMacro('errorResponse')) {
            Response::macro('errorResponse', function ($message, $data = [], $status = 400) {
                return Response::json(['status' => false, 'message' => $message, 'errors' => $data], $status);
            });
        }
    }
}
