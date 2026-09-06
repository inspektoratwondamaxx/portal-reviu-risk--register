<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AsbResource;
use App\Http\Resources\HspkResource;
use App\Http\Resources\SbuItemResource;
use App\Http\Resources\SshItemResource;
use App\Models\Asb;
use App\Models\Hspk;
use App\Models\SbuItem;
use App\Models\SshItem;
use App\Models\TahunAnggaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * API publik baca-saja (fondasi integrasi SIPD Level 2, Bab 21 kajian): menyajikan data standar
 * harga yang sudah aktif/dipublikasikan — data yang sama yang bisa dicari lewat website publik
 * tanpa login — dalam format JSON agar bisa dikonsumsi sistem lain.
 */
class HargaController extends Controller
{
    public function ringkasan(): JsonResponse
    {
        return response()->json(['data' => [
            'ssh' => SshItem::active()->count(),
            'sbu' => SbuItem::active()->count(),
            'hspk' => Hspk::active()->count(),
            'asb' => Asb::active()->count(),
            'tahun_aktif' => TahunAnggaran::aktif()?->tahun,
        ]]);
    }

    public function ssh(Request $request): AnonymousResourceCollection
    {
        $tahunId = $this->resolveTahunId($request);

        $items = SshItem::active()->with(['category', 'opd', 'tahunAnggaran'])
            ->when($request->string('q')->toString(), fn ($q, $term) => $q->search($term))
            ->when($request->string('kode')->toString(), fn ($q, $kode) => $q->where('kode_barang', 'ilike', "%{$kode}%"))
            ->when($tahunId !== null, fn ($q) => $q->where('tahun_anggaran_id', $tahunId))
            ->orderBy('uraian')
            ->paginate($this->perPage($request));

        return SshItemResource::collection($items);
    }

    public function sshShow(SshItem $ssh): SshItemResource|JsonResponse
    {
        abort_unless($ssh->is_active, 404, 'Data SSH tidak ditemukan atau belum aktif.');

        return new SshItemResource($ssh->load(['category', 'opd', 'tahunAnggaran']));
    }

    public function sbu(Request $request): AnonymousResourceCollection
    {
        $tahunId = $this->resolveTahunId($request);

        $items = SbuItem::active()->with(['opd', 'tahunAnggaran'])
            ->when($request->string('q')->toString(), fn ($q, $term) => $q->where('uraian', 'ilike', "%{$term}%"))
            ->when($request->string('kode')->toString(), fn ($q, $kode) => $q->where('kode', 'ilike', "%{$kode}%"))
            ->when($tahunId !== null, fn ($q) => $q->where('tahun_anggaran_id', $tahunId))
            ->orderBy('uraian')
            ->paginate($this->perPage($request));

        return SbuItemResource::collection($items);
    }

    public function sbuShow(SbuItem $sbu): SbuItemResource
    {
        abort_unless($sbu->is_active, 404, 'Data SBU tidak ditemukan atau belum aktif.');

        return new SbuItemResource($sbu->load(['opd', 'tahunAnggaran']));
    }

    public function hspk(Request $request): AnonymousResourceCollection
    {
        $tahunId = $this->resolveTahunId($request);

        $items = Hspk::active()->with(['tahunAnggaran'])
            ->when($request->string('q')->toString(), fn ($q, $term) => $q->where('uraian', 'ilike', "%{$term}%"))
            ->when($request->string('kode')->toString(), fn ($q, $kode) => $q->where('kode', 'ilike', "%{$kode}%"))
            ->when($tahunId !== null, fn ($q) => $q->where('tahun_anggaran_id', $tahunId))
            ->orderBy('uraian')
            ->paginate($this->perPage($request));

        return HspkResource::collection($items);
    }

    public function hspkShow(Hspk $hspk): HspkResource
    {
        abort_unless($hspk->is_active, 404, 'Data HSPK tidak ditemukan atau belum aktif.');

        return new HspkResource($hspk->load(['tahunAnggaran', 'components' => fn ($q) => $q->orderBy('urutan')]));
    }

    public function asb(Request $request): AnonymousResourceCollection
    {
        $tahunId = $this->resolveTahunId($request);

        $items = Asb::active()->with(['tahunAnggaran'])
            ->when($request->string('q')->toString(), fn ($q, $term) => $q->where('nama_kegiatan', 'ilike', "%{$term}%"))
            ->when($request->string('kode')->toString(), fn ($q, $kode) => $q->where('kode', 'ilike', "%{$kode}%"))
            ->when($tahunId !== null, fn ($q) => $q->where('tahun_anggaran_id', $tahunId))
            ->orderBy('nama_kegiatan')
            ->paginate($this->perPage($request));

        return AsbResource::collection($items);
    }

    public function asbShow(Asb $asb): AsbResource
    {
        abort_unless($asb->is_active, 404, 'Data ASB tidak ditemukan atau belum aktif.');

        return new AsbResource($asb->load(['tahunAnggaran', 'variables' => fn ($q) => $q->orderBy('urutan'), 'formula']));
    }

    /**
     * Null = parameter "tahun" tidak dikirim (jangan filter). Selain itu selalu kembalikan angka
     * (0 jika tahunnya tidak ada di data) agar filter tetap diterapkan dan menghasilkan 0 baris —
     * bukan diam-diam mengabaikan filter lalu menampilkan semua tahun.
     */
    private function resolveTahunId(Request $request): ?int
    {
        $tahun = $request->integer('tahun') ?: null;

        if (! $tahun) {
            return null;
        }

        return TahunAnggaran::where('tahun', $tahun)->value('id') ?? 0;
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->integer('per_page', 15), 1), 100);
    }
}
