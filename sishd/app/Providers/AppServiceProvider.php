<?php

namespace App\Providers;

use App\Models\Proposal;
use App\Models\TahunAnggaran;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
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
        Paginator::useBootstrapFive();

        Blade::if('role', fn (string ...$roles) => Auth::check() && Auth::user()->hasRole(...$roles));

        View::composer('layouts.app', function ($view) {
            $view->with('tahunAktifGlobal', TahunAnggaran::aktif());
            $view->with('menungguVerifikasiCount', Auth::check() && Auth::user()->hasRole(\App\Enums\RoleName::SuperAdmin, \App\Enums\RoleName::Verifikator)
                ? Proposal::where('status', 'menunggu_verifikasi')->count()
                : 0);
        });
    }
}
