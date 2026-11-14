<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Disposisi;
use App\Models\SuratMasuk;
use App\Models\User;
use App\Services\ArsipService;
use App\Services\NomorAgenda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SuratMasukController extends Controller
{
    public function index(Request $request)
    {
        $surat = SuratMasuk::dapatDilihat($request->user())
            ->cari($request->query('q'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('sifat'), fn ($q) => $q->where('sifat', $request->query('sifat')))
            ->when($request->filled('tahun'), fn ($q) => $q->where('tahun', (int) $request->query('tahun')))
            ->withCount('dokumen')
            ->orderByDesc('tahun')->orderByDesc('urut')
            ->paginate(15)->withQueryString();

        return view('surat-masuk.index', compact('surat'));
    }

    public function create()
    {
        return view('surat-masuk.create');
    }

    public function store(Request $request, ArsipService $arsip)
    {
        $data = $request->validate([
            'nomor_surat' => ['required', 'string', 'max:255'],
            'tanggal_surat' => ['required', 'date'],
            'tanggal_terima' => ['required', 'date', 'after_or_equal:tanggal_surat'],
            'asal_surat' => ['required', 'string', 'max:255'],
            'perihal' => ['required', 'string', 'max:1000'],
            'sifat' => ['required', Rule::in(SuratMasuk::SIFAT)],
            'klasifikasi' => ['nullable', 'string', 'max:30'],
            'ringkasan' => ['nullable', 'string', 'max:5000'],
            'berkas' => ['required', 'array', 'min:1', 'max:10'],
            'berkas.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'abaikan_duplikat' => ['nullable', 'boolean'],
        ], [
            'berkas.required' => 'Unggah minimal satu foto/scan/PDF surat.',
            'berkas.*.mimes' => 'Berkas harus berformat PDF, JPG, atau PNG.',
            'berkas.*.max' => 'Ukuran setiap berkas maksimal 10 MB.',
            'tanggal_terima.after_or_equal' => 'Tanggal terima tidak boleh sebelum tanggal surat.',
        ]);

        // Peringatan duplikat berkas (isi file identik dengan arsip yang sudah ada).
        if (! $request->boolean('abaikan_duplikat')) {
            foreach ($request->file('berkas') as $file) {
                if ($dup = $arsip->cariDuplikat($file)) {
                    return back()->withInput()->with('duplikat',
                        "Berkas \"{$file->getClientOriginalName()}\" identik dengan arsip yang sudah ada (".$dup->dokumenable?->nomor_agenda."). Centang \"tetap simpan\" bila memang disengaja.");
                }
            }
        }

        $surat = DB::transaction(function () use ($data, $request, $arsip) {
            $tahun = (int) date('Y', strtotime($data['tanggal_terima']));
            $nomor = NomorAgenda::berikutnya('surat_masuk', 'SM', $tahun);

            $surat = SuratMasuk::create([
                ...collect($data)->except(['berkas', 'abaikan_duplikat'])->all(),
                'tahun' => $tahun,
                'urut' => $nomor['urut'],
                'nomor_agenda' => $nomor['nomor_agenda'],
                'status' => 'baru',
                'dibuat_oleh' => $request->user()->id,
            ]);

            foreach (array_values($request->file('berkas')) as $i => $file) {
                $arsip->simpan($file, $surat, 'masuk', $request->user(), $i + 1);
            }

            AuditLog::catat('buat', $surat, ['nomor_agenda' => $surat->nomor_agenda, 'berkas' => count($request->file('berkas'))]);

            return $surat;
        });

        return redirect()->route('surat-masuk.show', $surat)->with('sukses', "Surat masuk tercatat dengan nomor agenda {$surat->nomor_agenda}.");
    }

    public function show(Request $request, SuratMasuk $suratMasuk)
    {
        abort_unless(SuratMasuk::dapatDilihat($request->user())->whereKey($suratMasuk->id)->exists(), 403);

        $suratMasuk->load(['dokumen', 'pembuat', 'disposisi.dari', 'disposisi.kepada']);
        AuditLog::catat('lihat', $suratMasuk);

        return view('surat-masuk.show', [
            'surat' => $suratMasuk,
            'penerima' => User::where('id', '!=', $request->user()->id)->orderBy('name')->get(),
            'daftarInstruksi' => Disposisi::INSTRUKSI,
        ]);
    }
}
