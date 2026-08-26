<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\BidangKegiatan;
use App\Models\Kampung;
use App\Models\Kegiatan;
use App\Models\KodeRekening;
use Illuminate\Http\Request;

class KegiatanController extends Controller
{
    public function index(Request $request)
    {
        $query = Kegiatan::query()->with(['kampung', 'bidangKegiatan']);

        if ($kampungId = $request->query('kampung_id')) {
            $query->where('kampung_id', $kampungId);
        }

        return view('admin.kegiatan.index', [
            'kegiatanList' => $query->orderBy('nama_kegiatan')->paginate(20)->withQueryString(),
            'kampungList' => Kampung::orderBy('nama_kampung')->get(),
            'bidangList' => BidangKegiatan::orderBy('kode')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kampung_id' => ['required', 'integer', 'exists:kampungs,id'],
            'bidang_kegiatan_id' => ['required', 'integer', 'exists:bidang_kegiatan,id'],
            'nama_kegiatan' => ['required', 'string', 'max:200'],
            'tahun_anggaran' => ['required', 'integer', 'digits:4'],
            'pagu_total' => ['required', 'numeric', 'min:0'],
        ]);

        Kegiatan::create($validated);

        return back()->with('status', 'Kegiatan berhasil ditambahkan.');
    }

    public function show(Kegiatan $kegiatan)
    {
        $kegiatan->load(['kampung', 'bidangKegiatan', 'paguRekening.kodeRekening']);

        return view('admin.kegiatan.show', [
            'kegiatan' => $kegiatan,
            'kodeRekeningList' => KodeRekening::where('tahun_anggaran', $kegiatan->tahun_anggaran)->orderBy('kode')->get(),
        ]);
    }

    public function setPagu(Request $request, Kegiatan $kegiatan)
    {
        $validated = $request->validate([
            'kode_rekening_id' => ['required', 'integer', 'exists:kode_rekening,id'],
            'pagu_anggaran' => ['required', 'numeric', 'min:0'],
        ]);

        $kegiatan->paguRekening()->updateOrCreate(
            ['kode_rekening_id' => $validated['kode_rekening_id']],
            ['pagu_anggaran' => $validated['pagu_anggaran']]
        );

        return back()->with('status', 'Pagu rekening berhasil disimpan.');
    }
}
