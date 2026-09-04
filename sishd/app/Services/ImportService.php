<?php

namespace App\Services;

use App\Models\AssetCode;
use App\Models\Category;
use App\Models\Import;
use App\Models\SbuItem;
use App\Models\SshItem;
use App\Models\TahunAnggaran;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Import Excel Master SSH/SBU (Bab 6-7 & 13 kajian). Dijalankan dari Master Data (admin), sehingga
 * baris valid langsung berstatus aktif — beda dari Usulan OPD yang wajib lewat verifikasi.
 * Baris yang miripp data lama tetap diimpor tapi ditandai di error_log sebagai peringatan, tidak
 * memblokir (kajian: fitur anti-duplikasi bersifat peringatan, keputusan tetap di admin).
 */
class ImportService
{
    public function __construct(private readonly DuplicateDetectionService $duplicateDetectionService)
    {
    }

    /** @return array{header: array<int, string>, rows: array<int, array<string, mixed>>} */
    public const SSH_COLUMNS = ['kode_aset', 'kode_rekening', 'uraian', 'spesifikasi', 'merek', 'satuan', 'harga', 'tahun', 'sumber_harga', 'keterangan'];

    public const SBU_COLUMNS = ['kode', 'kategori', 'uraian', 'satuan', 'wilayah', 'besaran', 'tahun', 'dasar_penetapan', 'keterangan'];

    public function importSsh(string $filePath, User $user): Import
    {
        $import = Import::create([
            'jenis' => 'ssh', 'file_path' => $filePath, 'file_name' => basename($filePath),
            'status' => 'processing', 'user_id' => $user->id,
        ]);

        $rows = $this->readRows($filePath, self::SSH_COLUMNS);
        $sukses = 0;
        $errors = [];

        foreach ($rows as $i => $row) {
            $baris = $i + 2;

            try {
                if (empty($row['uraian']) || empty($row['satuan']) || $row['harga'] === null) {
                    throw new \RuntimeException('Kolom uraian/satuan/harga wajib diisi.');
                }

                $tahun = TahunAnggaran::firstOrCreate(['tahun' => (int) ($row['tahun'] ?: now()->year)]);
                $assetCode = ! empty($row['kode_aset']) ? AssetCode::where('kode', $row['kode_aset'])->first() : null;

                $mirip = $this->duplicateDetectionService->findSimilar((string) $row['uraian'], $row['merek'] ?? null);

                SshItem::create([
                    'kode_barang' => $this->generateKode(SshItem::class, 'kode_barang', 'SSH'),
                    'asset_code_id' => $assetCode?->id,
                    'tahun_anggaran_id' => $tahun->id,
                    'uraian' => $row['uraian'],
                    'spesifikasi' => $row['spesifikasi'] ?? null,
                    'merek' => $row['merek'] ?? null,
                    'satuan' => $row['satuan'],
                    'harga' => (float) $row['harga'],
                    'sumber_harga' => $row['sumber_harga'] ?? 'Import Excel',
                    'keterangan' => $mirip->isNotEmpty()
                        ? trim(($row['keterangan'] ?? '')."\n[Peringatan: mirip dengan ".$mirip->count().' data lain]')
                        : ($row['keterangan'] ?? null),
                    'status' => 'aktif',
                    'is_active' => true,
                    'created_by' => $user->id,
                ]);

                $sukses++;
            } catch (\Throwable $e) {
                $errors[] = "Baris {$baris}: {$e->getMessage()}";
            }
        }

        $import->forceFill([
            'total_baris' => count($rows), 'sukses' => $sukses, 'gagal' => count($errors),
            'status' => count($errors) > 0 && $sukses === 0 ? 'gagal' : 'selesai',
            'error_log' => $errors ?: null,
        ])->save();

        return $import->refresh();
    }

    public function importSbu(string $filePath, User $user): Import
    {
        $import = Import::create([
            'jenis' => 'sbu', 'file_path' => $filePath, 'file_name' => basename($filePath),
            'status' => 'processing', 'user_id' => $user->id,
        ]);

        $rows = $this->readRows($filePath, self::SBU_COLUMNS);
        $sukses = 0;
        $errors = [];

        foreach ($rows as $i => $row) {
            $baris = $i + 2;

            try {
                if (empty($row['uraian']) || empty($row['satuan']) || $row['besaran'] === null) {
                    throw new \RuntimeException('Kolom uraian/satuan/besaran wajib diisi.');
                }

                $tahun = TahunAnggaran::firstOrCreate(['tahun' => (int) ($row['tahun'] ?: now()->year)]);

                SbuItem::create([
                    'kode' => $row['kode'] ?: $this->generateKode(SbuItem::class, 'kode', 'SBU'),
                    'kategori' => $row['kategori'] ?: 'lainnya',
                    'uraian' => $row['uraian'],
                    'satuan' => $row['satuan'],
                    'wilayah' => $row['wilayah'] ?? null,
                    'besaran' => (float) $row['besaran'],
                    'tahun_anggaran_id' => $tahun->id,
                    'dasar_penetapan' => $row['dasar_penetapan'] ?? 'Import Excel',
                    'keterangan' => $row['keterangan'] ?? null,
                    'status' => 'aktif',
                    'is_active' => true,
                    'created_by' => $user->id,
                ]);

                $sukses++;
            } catch (\Throwable $e) {
                $errors[] = "Baris {$baris}: {$e->getMessage()}";
            }
        }

        $import->forceFill([
            'total_baris' => count($rows), 'sukses' => $sukses, 'gagal' => count($errors),
            'status' => count($errors) > 0 && $sukses === 0 ? 'gagal' : 'selesai',
            'error_log' => $errors ?: null,
        ])->save();

        return $import->refresh();
    }

    /**
     * Bangkitkan kode master yang dijamin belum terpakai.
     *
     * Sebelumnya kode SSH dibangkitkan dari nomor baris ditambah angka acak 0-999, sedangkan kode
     * SBU dari nomor baris saja. Keduanya kolom UNIQUE, sehingga: impor puluhan baris pada tanggal
     * yang sama berpeluang besar bentrok sendiri (baris yang datanya benar gagal masuk dengan pesan
     * galat SQL mentah, dan barisnya berpindah-pindah tiap kali dijalankan), dan mengimpor berkas
     * kedua pada hari yang sama pasti bentrok di nomor baris yang sama. Karena itu nomor urut
     * dinaikkan sampai benar-benar bebas, bukan diundi.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    private function generateKode(string $model, string $kolom, string $prefix): string
    {
        $awalan = $prefix.'-'.now()->format('ymd').'-';
        $urutan = ((int) $model::max('id')) + 1;

        while ($model::where($kolom, $awalan.str_pad((string) $urutan, 4, '0', STR_PAD_LEFT))->exists()) {
            $urutan++;
        }

        return $awalan.str_pad((string) $urutan, 4, '0', STR_PAD_LEFT);
    }

    /** @param  array<int, string>  $columns */
    private function readRows(string $filePath, array $columns): array
    {
        $spreadsheet = IOFactory::load(Storage::disk('local')->path($filePath));
        $sheet = $spreadsheet->getActiveSheet();
        $rows = [];

        foreach ($sheet->toArray(null, true, true, false) as $i => $line) {
            if ($i === 0) {
                continue;
            }
            if (collect($line)->filter(fn ($v) => $v !== null && $v !== '')->isEmpty()) {
                continue;
            }
            $rows[] = array_combine($columns, array_pad(array_slice($line, 0, count($columns)), count($columns), null));
        }

        return $rows;
    }

    public function generateTemplate(string $jenis): string
    {
        $columns = $jenis === 'sbu' ? self::SBU_COLUMNS : self::SSH_COLUMNS;
        $contoh = $jenis === 'sbu'
            ? ['SBU-0001', 'honorarium', 'Honorarium Narasumber', 'OJ', 'Kabupaten', 500000, now()->year, 'SK Bupati', '']
            : ['1.3.01.01', '5.1.02.01.01.0001', 'Semen Portland 40 Kg', 'Tiga Roda', 'Tiga Roda', 'Zak', 82000, now()->year, 'Survei harga', ''];

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($columns, null, 'A1');
        $sheet->fromArray($contoh, null, 'A2');
        foreach (range('A', chr(ord('A') + count($columns) - 1)) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $path = "imports/template-{$jenis}.xlsx";
        $fullPath = Storage::disk('local')->path($path);
        Storage::disk('local')->makeDirectory('imports');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($fullPath);

        return $path;
    }
}
