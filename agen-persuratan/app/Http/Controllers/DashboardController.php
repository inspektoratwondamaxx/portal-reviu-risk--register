<?php

namespace App\Http\Controllers;

use App\Models\Disposisi;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();
        $awalBulan = now()->startOfMonth();
        $masuk = SuratMasuk::dapatDilihat($user);

        return view('dashboard', [
            'masukBulanIni' => (clone $masuk)->where('tanggal_terima', '>=', $awalBulan)->count(),
            'belumDisposisi' => $user->bolehLihatSemua() ? SuratMasuk::where('status', 'baru')->count() : 0,
            'keluarBulanIni' => $user->bolehLihatSemua() ? SuratKeluar::where('tanggal_surat', '>=', $awalBulan)->count() : 0,
            'disposisiSaya' => Disposisi::where('kepada_user_id', $user->id)->whereIn('status', ['menunggu', 'diproses'])->count(),
            'terbaru' => (clone $masuk)->latest('id')->limit(8)->get(),
            'disposisiUntukSaya' => Disposisi::with('suratMasuk', 'dari')
                ->where('kepada_user_id', $user->id)->whereIn('status', ['menunggu', 'diproses'])
                ->orderBy('batas_waktu')->limit(8)->get(),
        ]);
    }
}
