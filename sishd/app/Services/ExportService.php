<?php

namespace App\Services;

use App\Models\Asb;
use App\Models\Export;
use App\Models\Hspk;
use App\Models\SbuItem;
use App\Models\SshItem;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Modul Export (Bab 13 kajian): export biasa, per kelompok, per kode aset, dan format SIPD.
 * Excel hanya dipakai sebagai media pertukaran (Bab 19: "Excel hanya sebagai media import/export"),
 * bukan penyimpanan utama — sumber data tetap PostgreSQL.
 */
class ExportService
{
    /** @param  array<string, mixed>  $filters */
    public function exportSsh(array $filters, ?User $user = null): Export
    {
        $query = SshItem::query()->with(['assetCode', 'category', 'tahunAnggaran']);
        $this->applyCommonFilters($query, $filters);

        $rows = $query->orderBy('kode_barang')->get();

        $header = ['Kode Barang', 'Kode Aset', 'Uraian', 'Spesifikasi', 'Merek', 'Satuan', 'Harga', 'Tahun', 'Status'];
        $data = $rows->map(fn (SshItem $i) => [
            $i->kode_barang, $i->assetCode?->kode, $i->uraian, $i->spesifikasi, $i->merek,
            $i->satuan, (float) $i->harga, $i->tahunAnggaran?->tahun, $i->status->label(),
        ])->all();

        return $this->writeExport('ssh', 'excel', $filters, $header, $data, $user);
    }

    public function exportSbu(array $filters, ?User $user = null): Export
    {
        $query = SbuItem::query()->with('tahunAnggaran');
        $this->applyCommonFilters($query, $filters);

        $rows = $query->orderBy('kode')->get();

        $header = ['Kode', 'Kategori', 'Uraian', 'Satuan', 'Wilayah', 'Besaran', 'Tahun', 'Status'];
        $data = $rows->map(fn (SbuItem $i) => [
            $i->kode, SbuItem::KATEGORI[$i->kategori] ?? $i->kategori, $i->uraian, $i->satuan,
            $i->wilayah, (float) $i->besaran, $i->tahunAnggaran?->tahun, $i->status->label(),
        ])->all();

        return $this->writeExport('sbu', 'excel', $filters, $header, $data, $user);
    }

    public function exportHspk(array $filters, ?User $user = null): Export
    {
        $query = Hspk::query()->with('tahunAnggaran');
        $this->applyCommonFilters($query, $filters);

        $rows = $query->orderBy('kode')->get();

        $header = ['Kode', 'Uraian Pekerjaan', 'Jenis Pekerjaan', 'Satuan', 'Harga Satuan', 'Tahun', 'Status'];
        $data = $rows->map(fn (Hspk $i) => [
            $i->kode, $i->uraian, $i->jenis_pekerjaan, $i->satuan,
            (float) $i->harga_satuan, $i->tahunAnggaran?->tahun, $i->status->label(),
        ])->all();

        return $this->writeExport('hspk', 'excel', $filters, $header, $data, $user);
    }

    public function exportAsb(array $filters, ?User $user = null): Export
    {
        $query = Asb::query()->with('tahunAnggaran');
        $this->applyCommonFilters($query, $filters);

        $rows = $query->orderBy('kode')->get();

        $header = ['Kode', 'Nama Kegiatan', 'Kelompok Kegiatan', 'Satuan Variabel', 'Hasil Perhitungan', 'Tahun', 'Status'];
        $data = $rows->map(fn (Asb $i) => [
            $i->kode, $i->nama_kegiatan, $i->kelompok_kegiatan, $i->satuan_variabel,
            (float) $i->hasil_perhitungan, $i->tahunAnggaran?->tahun, $i->status->label(),
        ])->all();

        return $this->writeExport('asb', 'excel', $filters, $header, $data, $user);
    }

    /**
     * Format kolom mengikuti kebutuhan impor SIPD Level 1 (Bab 21 kajian). Struktur bersifat
     * praktik terbaik umum (kode SIPD, kode rekening, uraian, satuan, harga) dan perlu disesuaikan
     * dengan template resmi SIPD daerah masing-masing sebelum diunggah.
     */
    public function exportSipd(string $jenis, array $filters, ?User $user = null): Export
    {
        $header = ['Kode SIPD', 'Kode Rekening', 'Uraian', 'Spesifikasi', 'Satuan', 'Harga', 'Tahun Anggaran'];

        $data = match ($jenis) {
            'sbu' => SbuItem::query()->tap(fn ($q) => $this->applyCommonFilters($q, $filters))->with('tahunAnggaran')->get()
                ->map(fn (SbuItem $i) => ['', '', $i->uraian, '', $i->satuan, (float) $i->besaran, $i->tahunAnggaran?->tahun])->all(),
            default => SshItem::query()->tap(fn ($q) => $this->applyCommonFilters($q, $filters))->with(['accountCode', 'tahunAnggaran'])->get()
                ->map(fn (SshItem $i) => [
                    '', $i->accountCode?->kode, $i->uraian, $i->spesifikasi, $i->satuan, (float) $i->harga, $i->tahunAnggaran?->tahun,
                ])->all(),
        };

        return $this->writeExport($jenis, 'sipd', $filters, $header, $data, $user);
    }

    private function applyCommonFilters($query, array $filters): void
    {
        if (! empty($filters['tahun_anggaran_id'])) {
            $query->where('tahun_anggaran_id', $filters['tahun_anggaran_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->where('is_active', true);
        }
        if (! empty($filters['asset_code_id'])) {
            $query->where('asset_code_id', $filters['asset_code_id']);
        }
        if (! empty($filters['opd_id'])) {
            $query->where('opd_id', $filters['opd_id']);
        }
    }

    /** @param  array<int, string>  $header  @param  array<int, array<int, mixed>>  $data */
    private function writeExport(string $jenis, string $format, array $filters, array $header, array $data, ?User $user): Export
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($header, null, 'A1');
        $sheet->fromArray($data, null, 'A2');
        foreach (range('A', chr(ord('A') + count($header) - 1)) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = sprintf('%s-%s-%s.xlsx', $jenis, $format, now()->format('YmdHis'));
        $relativePath = "exports/{$fileName}";
        Storage::disk('local')->makeDirectory('exports');
        (new Xlsx($spreadsheet))->save(Storage::disk('local')->path($relativePath));

        return Export::create([
            'jenis' => $jenis,
            'format' => $format,
            'filter' => $filters ?: null,
            'file_path' => $relativePath,
            'total_baris' => count($data),
            'user_id' => $user?->id,
        ]);
    }
}
