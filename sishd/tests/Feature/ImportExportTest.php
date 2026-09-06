<?php

namespace Tests\Feature;

use App\Models\SbuItem;
use App\Models\SshItem;
use App\Models\TahunAnggaran;
use App\Models\User;
use App\Services\ExportService;
use App\Services\ImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Import/Export Excel — satu-satunya jalur yang menerima berkas dari luar sistem, sekaligus jalur
 * pertukaran data dengan SIPD saat ini. Yang dijaga di sini: seluruh baris valid benar-benar masuk
 * (tidak ada yang gagal diam-diam), baris bermasalah dilaporkan beserta nomor barisnya, dan berkas
 * yang sama boleh diimpor ulang tanpa saling menimpa kode.
 */
class ImportExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\OpdSeeder::class);
        $this->seed(\Database\Seeders\TahunAnggaranSeeder::class);
        $this->seed(\Database\Seeders\UserSeeder::class);

        $this->admin = User::where('email', 'adminssh@sishd.test')->firstOrFail();
    }

    /**
     * Tulis berkas .xlsx ke disk lokal (yang sudah di-fake) persis seperti berkas unggahan pengguna:
     * baris pertama judul kolom, sisanya data.
     *
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function buatBerkas(string $nama, array $columns, array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($columns, null, 'A1');
        $sheet->fromArray($rows, null, 'A2');

        $path = "imports/{$nama}";
        Storage::disk('local')->makeDirectory('imports');
        (new Xlsx($spreadsheet))->save(Storage::disk('local')->path($path));

        return $path;
    }

    /** @return array<int, array<int, mixed>> */
    private function barisSshValid(int $jumlah): array
    {
        $tahun = TahunAnggaran::aktif()->tahun;

        return collect(range(1, $jumlah))->map(fn (int $n) => [
            null, null, "Barang Uji Nomor {$n}", null, null, 'Unit', 10000 + $n, $tahun, 'Survei harga', null,
        ])->all();
    }

    // --- Import SSH ---

    /**
     * Regresi: kode barang hasil import pernah dibangkitkan dari nomor baris ditambah angka acak
     * 0-999. Karena kolomnya UNIQUE, impor puluhan baris pada tanggal yang sama berpeluang besar
     * bentrok — sebagian baris yang datanya benar gagal masuk dengan pesan galat SQL mentah, dan
     * kegagalannya berpindah-pindah tiap kali dijalankan. Impor 60 baris valid harus masuk semua.
     */
    public function test_seluruh_baris_valid_masuk_tanpa_ada_yang_gagal(): void
    {
        $path = $this->buatBerkas('ssh-60-baris.xlsx', ImportService::SSH_COLUMNS, $this->barisSshValid(60));

        $import = app(ImportService::class)->importSsh($path, $this->admin);

        $this->assertSame(60, $import->total_baris);
        $this->assertSame(0, $import->gagal, 'Baris valid tidak boleh gagal. Galat: '.json_encode($import->error_log));
        $this->assertSame(60, $import->sukses);
        $this->assertSame(60, SshItem::count());
        $this->assertSame('selesai', $import->status);
    }

    /** Kode barang yang dibangkitkan sistem harus unik satu sama lain. */
    public function test_kode_barang_hasil_import_unik(): void
    {
        $path = $this->buatBerkas('ssh-unik.xlsx', ImportService::SSH_COLUMNS, $this->barisSshValid(40));

        app(ImportService::class)->importSsh($path, $this->admin);

        $kode = SshItem::pluck('kode_barang');
        $this->assertCount(40, $kode);
        $this->assertSame($kode->count(), $kode->unique()->count(), 'Terdapat kode barang kembar.');
    }

    /** Berkas yang sama diimpor dua kali pada hari yang sama tidak boleh bentrok kode. */
    public function test_import_ulang_di_hari_sama_tetap_berhasil(): void
    {
        $path = $this->buatBerkas('ssh-ulang.xlsx', ImportService::SSH_COLUMNS, $this->barisSshValid(15));
        $service = app(ImportService::class);

        $pertama = $service->importSsh($path, $this->admin);
        $kedua = $service->importSsh($path, $this->admin);

        $this->assertSame(0, $pertama->gagal);
        $this->assertSame(0, $kedua->gagal, 'Import ulang gagal. Galat: '.json_encode($kedua->error_log));
        $this->assertSame(30, SshItem::count());
        $this->assertSame(30, SshItem::pluck('kode_barang')->unique()->count());
    }

    public function test_baris_bermasalah_dilaporkan_beserta_nomor_barisnya(): void
    {
        $tahun = TahunAnggaran::aktif()->tahun;
        $path = $this->buatBerkas('ssh-campuran.xlsx', ImportService::SSH_COLUMNS, [
            [null, null, 'Barang Lengkap', null, null, 'Unit', 15000, $tahun, 'Survei', null],
            [null, null, null, null, null, 'Unit', 20000, $tahun, 'Survei', null],      // uraian kosong
            [null, null, 'Barang Tanpa Satuan', null, null, null, 25000, $tahun, null, null], // satuan kosong
            [null, null, 'Barang Lengkap Kedua', null, null, 'Zak', 30000, $tahun, null, null],
        ]);

        $import = app(ImportService::class)->importSsh($path, $this->admin);

        $this->assertSame(4, $import->total_baris);
        $this->assertSame(2, $import->sukses);
        $this->assertSame(2, $import->gagal);
        $this->assertSame('selesai', $import->status, 'Sebagian berhasil berarti status selesai, bukan gagal total.');

        // Baris data pertama ada di baris 2 berkas (baris 1 judul kolom), jadi yang bermasalah = 3 dan 4.
        $this->assertStringContainsString('Baris 3', implode(' | ', $import->error_log));
        $this->assertStringContainsString('Baris 4', implode(' | ', $import->error_log));

        $this->assertNotNull(SshItem::where('uraian', 'Barang Lengkap')->first());
        $this->assertNotNull(SshItem::where('uraian', 'Barang Lengkap Kedua')->first());
        $this->assertNull(SshItem::where('uraian', 'Barang Tanpa Satuan')->first());
    }

    public function test_berkas_yang_seluruh_barisnya_bermasalah_berstatus_gagal(): void
    {
        $path = $this->buatBerkas('ssh-gagal-semua.xlsx', ImportService::SSH_COLUMNS, [
            [null, null, null, null, null, 'Unit', 1000, null, null, null],
            [null, null, null, null, null, 'Unit', 2000, null, null, null],
        ]);

        $import = app(ImportService::class)->importSsh($path, $this->admin);

        $this->assertSame(0, $import->sukses);
        $this->assertSame(2, $import->gagal);
        $this->assertSame('gagal', $import->status);
        $this->assertSame(0, SshItem::count());
    }

    public function test_baris_kosong_di_tengah_berkas_diabaikan(): void
    {
        $tahun = TahunAnggaran::aktif()->tahun;
        $path = $this->buatBerkas('ssh-baris-kosong.xlsx', ImportService::SSH_COLUMNS, [
            [null, null, 'Barang Satu', null, null, 'Unit', 11000, $tahun, null, null],
            [null, null, null, null, null, null, null, null, null, null],
            [null, null, 'Barang Dua', null, null, 'Unit', 12000, $tahun, null, null],
        ]);

        $import = app(ImportService::class)->importSsh($path, $this->admin);

        $this->assertSame(2, $import->total_baris, 'Baris kosong tidak dihitung sebagai data.');
        $this->assertSame(2, $import->sukses);
        $this->assertSame(0, $import->gagal);
    }

    public function test_import_tercatat_atas_nama_penggunanya(): void
    {
        $path = $this->buatBerkas('ssh-pencatatan.xlsx', ImportService::SSH_COLUMNS, $this->barisSshValid(2));

        $import = app(ImportService::class)->importSsh($path, $this->admin);

        $this->assertSame($this->admin->id, $import->user_id);
        $this->assertSame('ssh', $import->jenis);
        $this->assertSame('ssh-pencatatan.xlsx', $import->file_name);
        $this->assertSame($this->admin->id, SshItem::first()->created_by);
    }

    // --- Import SBU ---

    public function test_import_sbu_memakai_kode_dari_berkas_bila_ada(): void
    {
        $tahun = TahunAnggaran::aktif()->tahun;
        $path = $this->buatBerkas('sbu-berkode.xlsx', ImportService::SBU_COLUMNS, [
            ['SBU-UJI-001', 'honorarium', 'Honorarium Narasumber Uji', 'OJ', 'Kabupaten', 900000, $tahun, 'SK Bupati', null],
        ]);

        $import = app(ImportService::class)->importSbu($path, $this->admin);

        $this->assertSame(0, $import->gagal);
        $item = SbuItem::where('kode', 'SBU-UJI-001')->first();
        $this->assertNotNull($item);
        $this->assertEquals(900000, $item->besaran);
        $this->assertSame('honorarium', $item->kategori);
    }

    /** Tanpa kolom kode, sistem membangkitkan sendiri — dua berkas di hari sama tidak boleh bentrok. */
    public function test_import_sbu_tanpa_kode_tidak_bentrok_antar_berkas(): void
    {
        $tahun = TahunAnggaran::aktif()->tahun;
        $baris = [
            [null, 'honorarium', 'SBU Tanpa Kode Satu', 'OJ', null, 100000, $tahun, null, null],
            [null, 'konsumsi', 'SBU Tanpa Kode Dua', 'OK', null, 200000, $tahun, null, null],
        ];
        $service = app(ImportService::class);

        $pertama = $service->importSbu($this->buatBerkas('sbu-a.xlsx', ImportService::SBU_COLUMNS, $baris), $this->admin);
        $kedua = $service->importSbu($this->buatBerkas('sbu-b.xlsx', ImportService::SBU_COLUMNS, $baris), $this->admin);

        $this->assertSame(0, $pertama->gagal);
        $this->assertSame(0, $kedua->gagal, 'Berkas kedua gagal. Galat: '.json_encode($kedua->error_log));
        $this->assertSame(4, SbuItem::count());
        $this->assertSame(4, SbuItem::pluck('kode')->unique()->count());
    }

    public function test_import_sbu_menolak_baris_tanpa_besaran(): void
    {
        $tahun = TahunAnggaran::aktif()->tahun;
        $path = $this->buatBerkas('sbu-tanpa-besaran.xlsx', ImportService::SBU_COLUMNS, [
            ['SBU-X-1', 'honorarium', 'SBU Tanpa Besaran', 'OJ', null, null, $tahun, null, null],
        ]);

        $import = app(ImportService::class)->importSbu($path, $this->admin);

        $this->assertSame(0, $import->sukses);
        $this->assertSame(1, $import->gagal);
        $this->assertSame(0, SbuItem::count());
    }

    // --- Templat ---

    public function test_templat_memakai_judul_kolom_yang_sama_dengan_yang_dibaca_importer(): void
    {
        $service = app(ImportService::class);

        foreach (['ssh' => ImportService::SSH_COLUMNS, 'sbu' => ImportService::SBU_COLUMNS] as $jenis => $columns) {
            $path = $service->generateTemplate($jenis);
            $this->assertTrue(Storage::disk('local')->exists($path));

            $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(Storage::disk('local')->path($path))->getActiveSheet();
            $header = array_slice($sheet->toArray(null, true, true, false)[0], 0, count($columns));

            $this->assertSame($columns, $header, "Judul kolom templat {$jenis} harus sama persis dengan yang dibaca importer.");
        }
    }

    /** Templat harus benar-benar bisa diimpor kembali apa adanya (baris contohnya valid). */
    public function test_templat_ssh_bisa_langsung_diimpor(): void
    {
        $service = app(ImportService::class);
        $path = $service->generateTemplate('ssh');

        $import = $service->importSsh($path, $this->admin);

        $this->assertSame(1, $import->total_baris);
        $this->assertSame(0, $import->gagal, 'Baris contoh pada templat harus lolos validasi. Galat: '.json_encode($import->error_log));
    }

    // --- Export ---

    public function test_export_ssh_berisi_data_dan_judul_kolom(): void
    {
        $tahun = TahunAnggaran::aktif();
        SshItem::create([
            'kode_barang' => 'EXP-0001', 'tahun_anggaran_id' => $tahun->id, 'uraian' => 'Semen Uji Export',
            'satuan' => 'Zak', 'harga' => 82000, 'status' => 'aktif', 'is_active' => true,
        ]);

        $export = app(ExportService::class)->exportSsh([], $this->admin);

        $this->assertTrue(Storage::disk('local')->exists($export->file_path));

        $isi = \PhpOffice\PhpSpreadsheet\IOFactory::load(Storage::disk('local')->path($export->file_path))
            ->getActiveSheet()->toArray(null, true, true, false);

        $this->assertSame('Kode Barang', $isi[0][0]);
        $this->assertSame('EXP-0001', $isi[1][0]);
        $this->assertSame('Semen Uji Export', $isi[1][2]);
        $this->assertEquals(82000, $isi[1][6]);
    }

    /** Export tanpa hasil tetap menghasilkan berkas berisi judul kolom, bukan galat. */
    public function test_export_tanpa_data_tetap_menghasilkan_berkas(): void
    {
        $export = app(ExportService::class)->exportSsh([], $this->admin);

        $this->assertTrue(Storage::disk('local')->exists($export->file_path));

        $isi = \PhpOffice\PhpSpreadsheet\IOFactory::load(Storage::disk('local')->path($export->file_path))
            ->getActiveSheet()->toArray(null, true, true, false);

        $this->assertSame('Kode Barang', $isi[0][0]);
        $this->assertCount(1, array_filter($isi, fn ($baris) => collect($baris)->filter()->isNotEmpty()));
    }

    public function test_export_sbu_dan_sipd_menghasilkan_berkas(): void
    {
        $tahun = TahunAnggaran::aktif();
        SbuItem::create([
            'kode' => 'SBU-EXP-1', 'kategori' => 'honorarium', 'tahun_anggaran_id' => $tahun->id,
            'uraian' => 'Honorarium Uji Export', 'satuan' => 'OJ', 'besaran' => 500000,
            'status' => 'aktif', 'is_active' => true,
        ]);

        $sbu = app(ExportService::class)->exportSbu([], $this->admin);
        $sipd = app(ExportService::class)->exportSipd('ssh', [], $this->admin);

        $this->assertTrue(Storage::disk('local')->exists($sbu->file_path));
        $this->assertTrue(Storage::disk('local')->exists($sipd->file_path));
        $this->assertSame($this->admin->id, $sbu->user_id);
    }
}
