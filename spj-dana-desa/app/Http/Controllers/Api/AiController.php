<?php

namespace App\Http\Controllers\Api;

use App\Contracts\NarasiAiGenerator;
use App\Http\Controllers\Controller;
use App\Models\BuktiTransaksi;
use App\Models\ChatAiLog;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Bab VI.4 kajian teknis. KNF-10: seluruh keluaran AI berstatus draft/rekomendasi, wajib
 * verifikasi manusia sebelum final — endpoint di sini tidak pernah mengubah status transaksi
 * secara langsung menjadi final.
 */
class AiController extends Controller
{
    public function __construct(private readonly NarasiAiGenerator $narasiGenerator) {}

    public function ocrStatus(BuktiTransaksi $bukti)
    {
        $this->authorize('view', $bukti->transaksi);

        return $this->ok([
            'status_ocr' => $bukti->status_ocr,
            'hasil_ocr_raw' => $bukti->hasil_ocr_raw,
        ]);
    }

    public function narasi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaksi_id' => ['required', 'integer', 'exists:transaksis,id'],
        ]);

        if ($validator->fails()) {
            return $this->fail('Data tidak valid.', $validator->errors()->toArray());
        }

        $transaksi = Transaksi::findOrFail($request->integer('transaksi_id'));
        $this->authorize('view', $transaksi);

        return $this->ok([
            'narasi_draft' => $this->narasiGenerator->susunNarasi($transaksi),
            'status' => 'draft',
        ]);
    }

    /**
     * KF-08: bandingkan total realisasi kode rekening (termasuk transaksi ini, yang sudah
     * tersimpan) terhadap pagu anggarannya pada kegiatan terkait.
     */
    public function cekKewajaran(Transaksi $transaksi)
    {
        $this->authorize('view', $transaksi);

        $ambangPersen = config('spj.ambang_kewajaran_persen', 90);

        $paguRekening = (float) ($transaksi->kegiatan->paguRekening()
            ->where('kode_rekening_id', $transaksi->kode_rekening_id)
            ->value('pagu_anggaran') ?? 0);

        $totalTerpakai = (float) $transaksi->kegiatan->transaksis()
            ->where('kode_rekening_id', $transaksi->kode_rekening_id)
            ->whereNotIn('status', [Transaksi::STATUS_REVISI])
            ->sum('nominal');

        $sisaPagu = round($paguRekening - $totalTerpakai, 2);
        $persenTerpakai = $paguRekening > 0 ? round($totalTerpakai / $paguRekening * 100, 2) : 100.0;

        $melebihiPagu = $sisaPagu < 0;
        $flagged = $melebihiPagu || $persenTerpakai >= $ambangPersen;

        $catatan = $melebihiPagu
            ? 'Total realisasi kode rekening ini melebihi pagu anggaran.'
            : ($flagged ? "Realisasi kode rekening telah mencapai {$persenTerpakai}% dari pagu (ambang {$ambangPersen}%)." : null);

        $transaksi->update(['is_flagged' => $flagged, 'catatan_flag' => $catatan]);

        return $this->ok([
            'pagu_rekening' => $paguRekening,
            'total_terpakai' => $totalTerpakai,
            'sisa_pagu' => $sisaPagu,
            'persen_terpakai' => $persenTerpakai,
            'is_flagged' => $flagged,
            'catatan' => $catatan,
        ]);
    }

    public function chat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pertanyaan' => ['required', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return $this->fail('Data tidak valid.', $validator->errors()->toArray());
        }

        $user = $request->user();

        // Stub tanpa layanan LLM eksternal — lihat README. Tetap dicatat ke chat_ai_logs agar
        // pola pertanyaan pengguna dapat dianalisis saat integrasi AI sungguhan dipasang.
        $jawaban = 'Asisten AI belum terhubung ke layanan eksternal pada tahap ini. '
            .'Silakan hubungi Pendamping Desa/helpdesk untuk pertanyaan: "'.$request->string('pertanyaan').'"';

        $log = ChatAiLog::create([
            'user_id' => $user->id,
            'kampung_id' => $user->kampung_id,
            'pertanyaan' => $request->string('pertanyaan'),
            'jawaban' => $jawaban,
        ]);

        return $this->ok($log);
    }
}
