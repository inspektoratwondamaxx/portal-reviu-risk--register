<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessOcrBukti;
use App\Models\BuktiTransaksi;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/** Bab VI.3 kajian teknis — aplikasi Android Kaur Keuangan. */
class TransaksiController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Transaksi::class);

        $query = Transaksi::query()->with(['kegiatan', 'kodeRekening', 'buktiTransaksi']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($bulan = $request->query('bulan')) {
            $query->whereMonth('tanggal_transaksi', $bulan);
        }

        if ($tahun = $request->query('tahun')) {
            $query->whereYear('tanggal_transaksi', $tahun);
        }

        $transaksis = $query->orderByDesc('tanggal_transaksi')->paginate($request->integer('per_page', 20));

        return $this->ok($transaksis->items(), [
            'current_page' => $transaksis->currentPage(),
            'last_page' => $transaksis->lastPage(),
            'total' => $transaksis->total(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Transaksi::class);

        $validator = Validator::make($request->all(), [
            'uuid' => ['required', 'uuid'],
            'kegiatan_id' => ['required', 'integer', 'exists:kegiatan,id'],
            'kode_rekening_id' => ['required', 'integer', 'exists:kode_rekening,id'],
            'tanggal_transaksi' => ['required', 'date'],
            'uraian' => ['required', 'string'],
            'nominal' => ['required', 'numeric', 'min:0.01'],
            'sumber_input' => ['sometimes', 'in:manual,ocr_ai'],
            'dibuat_offline' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->fail('Data tidak valid.', $validator->errors()->toArray());
        }

        // Idempotency sinkronisasi offline (KF-04, Bab VI.1): uuid duplikat mengembalikan
        // data yang sudah ada, bukan menciptakan baris baru.
        $existing = Transaksi::where('uuid', $request->string('uuid'))->first();

        if ($existing) {
            return $this->ok($existing, status: 200);
        }

        $user = $request->user();

        $transaksi = Transaksi::create([
            ...$validator->validated(),
            'kampung_id' => $user->kampung_id,
        ]);

        return $this->ok($transaksi, status: 201);
    }

    public function show(Transaksi $transaksi)
    {
        $this->authorize('view', $transaksi);

        return $this->ok($transaksi->load(['kegiatan', 'kodeRekening', 'buktiTransaksi', 'riwayatStatus.pengubah']));
    }

    public function update(Request $request, Transaksi $transaksi)
    {
        $this->authorize('update', $transaksi);

        $validator = Validator::make($request->all(), [
            'kegiatan_id' => ['sometimes', 'integer', 'exists:kegiatan,id'],
            'kode_rekening_id' => ['sometimes', 'integer', 'exists:kode_rekening,id'],
            'tanggal_transaksi' => ['sometimes', 'date'],
            'uraian' => ['sometimes', 'string'],
            'nominal' => ['sometimes', 'numeric', 'min:0.01'],
        ]);

        if ($validator->fails()) {
            return $this->fail('Data tidak valid.', $validator->errors()->toArray());
        }

        $transaksi->update($validator->validated());

        return $this->ok($transaksi->fresh());
    }

    public function uploadBukti(Request $request, Transaksi $transaksi)
    {
        $this->authorize('uploadBukti', $transaksi);

        $validator = Validator::make($request->all(), [
            'foto' => ['required', 'image', 'max:'.config('spj.maks_ukuran_bukti_kb', 800) * 4],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'diambil_at' => ['required', 'date'],
        ]);

        if ($validator->fails()) {
            return $this->fail('Data tidak valid.', $validator->errors()->toArray());
        }

        $tahun = $transaksi->tanggal_transaksi->year;
        $path = $request->file('foto')->store(
            "{$transaksi->kampung_id}/{$tahun}/{$transaksi->id}",
            'bukti'
        );

        $bukti = $transaksi->buktiTransaksi()->create([
            'path_file' => $path,
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'diambil_at' => $request->input('diambil_at'),
            'status_ocr' => BuktiTransaksi::OCR_DIPROSES,
        ]);

        ProcessOcrBukti::dispatch($bukti->id);

        return $this->ok($bukti, status: 201);
    }

    public function verifikasiOcr(Request $request, Transaksi $transaksi)
    {
        $this->authorize('verifikasiOcr', $transaksi);

        $validator = Validator::make($request->all(), [
            'uraian' => ['sometimes', 'string'],
            'nominal' => ['sometimes', 'numeric', 'min:0.01'],
            'kode_rekening_id' => ['sometimes', 'integer', 'exists:kode_rekening,id'],
        ]);

        if ($validator->fails()) {
            return $this->fail('Data tidak valid.', $validator->errors()->toArray());
        }

        $transaksi->fill($validator->validated());
        $transaksi->save();
        $transaksi->ubahStatus(Transaksi::STATUS_TERVERIFIKASI, 'Diverifikasi/dikoreksi manual oleh Kaur Keuangan.');

        return $this->ok($transaksi->fresh());
    }

    public function ajukan(Transaksi $transaksi)
    {
        $this->authorize('ajukan', $transaksi);

        $transaksi->ubahStatus(Transaksi::STATUS_DIAJUKAN, 'Diajukan ke alur persetujuan.');

        return $this->ok($transaksi->fresh());
    }

    /** POST /transaksi/sync-batch — sinkronisasi banyak transaksi offline sekaligus (KF-04). */
    public function syncBatch(Request $request)
    {
        $this->authorize('create', Transaksi::class);

        $validator = Validator::make($request->all(), [
            'transaksis' => ['required', 'array', 'min:1'],
            'transaksis.*.uuid' => ['required', 'uuid'],
            'transaksis.*.kegiatan_id' => ['required', 'integer', 'exists:kegiatan,id'],
            'transaksis.*.kode_rekening_id' => ['required', 'integer', 'exists:kode_rekening,id'],
            'transaksis.*.tanggal_transaksi' => ['required', 'date'],
            'transaksis.*.uraian' => ['required', 'string'],
            'transaksis.*.nominal' => ['required', 'numeric', 'min:0.01'],
        ]);

        if ($validator->fails()) {
            return $this->fail('Data tidak valid.', $validator->errors()->toArray());
        }

        $user = $request->user();
        $hasil = [];

        foreach ($validator->validated()['transaksis'] as $item) {
            $existing = Transaksi::where('uuid', $item['uuid'])->first();

            if ($existing) {
                $hasil[] = ['uuid' => $item['uuid'], 'id' => $existing->id, 'status' => 'sudah_ada'];

                continue;
            }

            $transaksi = Transaksi::create([
                ...$item,
                'kampung_id' => $user->kampung_id,
                'sumber_input' => Transaksi::SUMBER_MANUAL,
                'dibuat_offline' => true,
            ]);

            $hasil[] = ['uuid' => $item['uuid'], 'id' => $transaksi->id, 'status' => 'tersinkron'];
        }

        return $this->ok($hasil, status: 201);
    }
}
