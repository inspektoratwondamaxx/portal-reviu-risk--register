<?php

namespace App\Providers;

use App\Contracts\NarasiAiGenerator;
use App\Services\TemplateNarasiAiGenerator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Ganti binding ini dengan penyedia LLM sungguhan saat Tahap 3 (lihat README).
        $this->app->bind(NarasiAiGenerator::class, TemplateNarasiAiGenerator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
