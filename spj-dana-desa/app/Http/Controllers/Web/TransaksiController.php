<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;

class TransaksiController extends Controller
{
    public function show(Transaksi $transaksi)
    {
        $this->authorize('view', $transaksi);

        $transaksi->load(['kampung', 'kegiatan', 'kodeRekening', 'buktiTransaksi', 'riwayatStatus.pengubah']);

        return view('transaksi.show', compact('transaksi'));
    }
}
