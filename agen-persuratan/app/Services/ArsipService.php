<?php

namespace App\Services;

use App\Models\Dokumen;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Menyimpan berkas surat (foto/scan/PDF) ke folder arsip yang terstruktur:
 *   {jenis}/{tahun}/{bulan}/{urut}_{asal-atau-tujuan}_{perihal}.{ext}
 * Berkas asli tidak pernah ditimpa; setiap berkas dicatat dengan hash SHA-256.
 */
class ArsipService
{
    public const DISK = 'arsip';

    public function simpan(UploadedFile $berkas, Model $surat, string $jenis, User $pengunggah, int $nomorBerkas = 1): Dokumen
    {
        $tanggal = $jenis === 'masuk' ? $surat->tanggal_terima : $surat->tanggal_surat;
        $pihak = $jenis === 'masuk' ? $surat->asal_surat : $surat->tujuan;

        $ekstensi = strtolower($berkas->guessExtension() ?: $berkas->getClientOriginalExtension());
        $nama = sprintf(
            '%04d_%s_%s%s.%s',
            $surat->urut,
            Str::limit(Str::slug($pihak), 30, ''),
            Str::limit(Str::slug($surat->perihal), 40, ''),
            $nomorBerkas > 1 ? "-{$nomorBerkas}" : '',
            $ekstensi
        );
        $folder = sprintf('%s/%d/%02d', $jenis, $tanggal->year, $tanggal->month);
        $path = $this->pathUnik($folder, $nama);

        Storage::disk(self::DISK)->putFileAs($folder, $berkas, basename($path));

        return $surat->dokumen()->create([
            'nama_asli' => $berkas->getClientOriginalName(),
            'path' => $path,
            'mime' => $berkas->getMimeType() ?: 'application/octet-stream',
            'ukuran' => $berkas->getSize(),
            'hash' => hash_file('sha256', $berkas->getRealPath()),
            'diunggah_oleh' => $pengunggah->id,
        ]);
    }

    /** Mengembalikan dokumen lain dengan isi identik (duplikat), jika ada. */
    public function cariDuplikat(UploadedFile $berkas): ?Dokumen
    {
        return Dokumen::where('hash', hash_file('sha256', $berkas->getRealPath()))->first();
    }

    private function pathUnik(string $folder, string $nama): string
    {
        $disk = Storage::disk(self::DISK);
        $info = pathinfo($nama);
        $kandidat = "$folder/$nama";
        $i = 2;
        while ($disk->exists($kandidat)) {
            $kandidat = sprintf('%s/%s_v%d.%s', $folder, $info['filename'], $i++, $info['extension'] ?? 'bin');
        }

        return $kandidat;
    }
}
