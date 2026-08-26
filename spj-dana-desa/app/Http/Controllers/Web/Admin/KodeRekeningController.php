<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\KodeRekening;
use Illuminate\Http\Request;

class KodeRekeningController extends Controller
{
    public function index()
    {
        return view('admin.kode-rekening.index', [
            'kodeRekeningList' => KodeRekening::orderByDesc('tahun_anggaran')->orderBy('kode')->paginate(50),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:20'],
            'uraian' => ['required', 'string', 'max:200'],
            'jenis_belanja' => ['required', 'in:pegawai,barang_jasa,modal,tak_terduga'],
            'tahun_anggaran' => ['required', 'integer', 'digits:4'],
        ]);

        $exists = KodeRekening::where('kode', $validated['kode'])
            ->where('tahun_anggaran', $validated['tahun_anggaran'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['kode' => 'Kode rekening sudah terdaftar pada tahun anggaran tersebut.']);
        }

        KodeRekening::create($validated);

        return back()->with('status', 'Kode rekening berhasil ditambahkan.');
    }
}
