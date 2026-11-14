<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use App\Services\ArsipService;
use App\Services\NomorAgenda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SuratKeluarController extends Controller
{
    public function index(Request $request)
    {
        $surat = SuratKeluar::cari($request->query('q'))
            ->when($request->filled('tahun'), fn ($q) => $q->where('tahun', (int) $request->query('tahun')))
            ->withCount('dokumen')
            ->orderByDesc('tahun')->orderByDesc('urut')
            ->paginate(15)->withQueryString();

        return view('surat-keluar.index', compact('surat'));
    }

    public function create()
    {
        return view('surat-keluar.create');
    }

    public function store(Request $request, ArsipService $arsip)
    {
        $data = $request->validate([
            'nomor_surat' => ['required', 'string', 'max:255'],
            'tanggal_surat' => ['required', 'date'],
            'tujuan' => ['required', 'string', 'max:255'],
            'perihal' => ['required', 'string', 'max:1000'],
            'sifat' => ['required', Rule::in(SuratMasuk::SIFAT)],
            'klasifikasi' => ['nullable', 'string', 'max:30'],
            'ringkasan' => ['nullable', 'string', 'max:5000'],
            'berkas' => ['nullable', 'array', 'max:10'],
            'berkas.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], [
            'berkas.*.mimes' => 'Berkas harus berformat PDF, JPG, atau PNG.',
            'berkas.*.max' => 'Ukuran setiap berkas maksimal 10 MB.',
        ]);

        $surat = DB::transaction(function () use ($data, $request, $arsip) {
            $tahun = (int) date('Y', strtotime($data['tanggal_surat']));
            $nomor = NomorAgenda::berikutnya('surat_keluar', 'SK', $tahun);

            $surat = SuratKeluar::create([
                ...collect($data)->except('berkas')->all(),
                'tahun' => $tahun,
                'urut' => $nomor['urut'],
                'nomor_agenda' => $nomor['nomor_agenda'],
                'dibuat_oleh' => $request->user()->id,
            ]);

            foreach (array_values($request->file('berkas', [])) as $i => $file) {
                $arsip->simpan($file, $surat, 'keluar', $request->user(), $i + 1);
            }

            AuditLog::catat('buat', $surat, ['nomor_agenda' => $surat->nomor_agenda]);

            return $surat;
        });

        return redirect()->route('surat-keluar.show', $surat)->with('sukses', "Surat keluar tercatat dengan nomor agenda {$surat->nomor_agenda}.");
    }

    public function show(SuratKeluar $suratKeluar)
    {
        $suratKeluar->load(['dokumen', 'pembuat']);
        AuditLog::catat('lihat', $suratKeluar);

        return view('surat-keluar.show', ['surat' => $suratKeluar]);
    }
}
