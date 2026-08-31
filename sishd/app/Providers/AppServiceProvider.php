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
            $view->with('menungguVerifikasiCount', $this->menungguVerifikasiCount());
        });
    }

    /** Badge sidebar "Verifikasi Usulan" — jumlah menyesuaikan tahap approval berjenjang milik user. */
    private function menungguVerifikasiCount(): int
    {
        if (! Auth::check()) {
            return 0;
        }

        $user = Auth::user();

        if ($user->isSuperAdmin()) {
            return Proposal::where('status', 'menunggu_verifikasi')->count();
        }

        foreach (Proposal::TAHAPAN_URUTAN as $tahapan) {
            if ($user->hasRole(Proposal::roleForTahapan($tahapan))) {
                return Proposal::where('status', 'menunggu_verifikasi')->where('tahapan_saat_ini', $tahapan)->count();
            }
        }

        return 0;
    }
}
