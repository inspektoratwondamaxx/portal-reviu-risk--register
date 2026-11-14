<?php

namespace App\Http\Controllers;

use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use Illuminate\Http\Request;

class PencarianController extends Controller
{
    public function __invoke(Request $request)
    {
        $kata = trim((string) $request->query('q'));
        $user = $request->user();
        $masuk = collect();
        $keluar = collect();

        if ($kata !== '') {
            $masuk = SuratMasuk::dapatDilihat($user)->cari($kata)->latest('tanggal_terima')->limit(50)->get();
            if ($user->bolehLihatSemua()) {
                $keluar = SuratKeluar::cari($kata)->latest('tanggal_surat')->limit(50)->get();
            }
        }

        return view('cari', compact('kata', 'masuk', 'keluar'));
    }
}
