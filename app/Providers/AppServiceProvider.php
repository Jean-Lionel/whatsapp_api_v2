<?php

namespace App\Providers;

use App\Models\ApiKey;
use App\Models\ClientWebhook;
use App\Models\WhatsappConfiguration;
use App\Policies\ApiKeyPolicy;
use App\Policies\ClientWebhookPolicy;
use App\Policies\WhatsappConfigurationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(ApiKey::class, ApiKeyPolicy::class);
        Gate::policy(ClientWebhook::class, ClientWebhookPolicy::class);
        Gate::policy(WhatsappConfiguration::class, WhatsappConfigurationPolicy::class);
    }
}
