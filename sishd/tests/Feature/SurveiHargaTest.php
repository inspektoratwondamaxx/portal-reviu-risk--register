<?php

namespace Tests\Feature;

use App\Models\PriceEvidence;
use App\Models\PriceSurvey;
use App\Models\SshItem;
use App\Models\TahunAnggaran;
use App\Models\User;
use App\Models\Vendor;
use App\Services\PriceSurveyAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Survei harga lapangan (Bab 15 & 22.4 kajian). Modul ini yang menentukan angka harga masuk ke
 * SSH, jadi yang diuji bukan sekadar "form tersimpan", melainkan: angka rekomendasinya benar
 * secara statistik (median, bukan rata-rata yang gampang ditarik satu harga nyeleneh), bukti
 * digitalnya benar-benar tersimpan dan ikut terhapus, serta siapa saja yang boleh membukanya.
 */
class SurveiHargaTest extends TestCase
{
    use RefreshDatabase;

    private TahunAnggaran $tahun;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\OpdSeeder::class);
        $this->seed(\Database\Seeders\TahunAnggaranSeeder::class);
        $this->seed(\Database\Seeders\UserSeeder::class);

        $this->tahun = TahunAnggaran::aktif();
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function buatSsh(string $uraian = 'Semen Portland 40 Kg'): SshItem
    {
        return SshItem::create([
            'kode_barang' => 'SSH-'.fake()->unique()->numerify('#####'),
            'tahun_anggaran_id' => $this->tahun->id,
            'uraian' => $uraian,
            'satuan' => 'Zak',
            'harga' => 65000,
            'status' => 'aktif',
            'is_active' => true,
        ]);
    }

    private function buatSurvei(array $atribut = []): PriceSurvey
    {
        return PriceSurvey::create($atribut + [
            'uraian_barang' => 'Semen Portland 40 Kg',
            'tanggal_survei' => '2026-03-01',
            'harga' => 65000,
        ]);
    }

    // --- Perekaman survei ---

    public function test_admin_ssh_dapat_membuka_daftar_survei(): void
    {
        $this->actingAs($this->user('adminssh@sishd.test'))
            ->get(route('survei-harga.index'))
            ->assertOk();
    }

    public function test_survei_tersimpan_dengan_surveyor_pengguna_yang_login(): void
    {
        $operator = $this->user('operator@sishd.test');
        $ssh = $this->buatSsh();
        $vendor = Vendor::create(['nama' => 'Toko Bangunan Jaya', 'is_active' => true]);

        $this->actingAs($operator)->post(route('survei-harga.store'), [
            'ssh_item_id' => $ssh->id,
            'uraian_barang' => 'Semen Portland 40 Kg',
            'merek' => 'Tonasa',
            'vendor_id' => $vendor->id,
            'lokasi' => 'Pasar Sentral',
            'tanggal_survei' => '2026-03-05',
            'harga' => 67500,
        ])->assertRedirect(route('survei-harga.index'));

        $survei = PriceSurvey::firstOrFail();

        // surveyor_id tidak boleh diambil dari input — harus dari sesi login, supaya jejak siapa
        // yang mensurvei tidak bisa dipalsukan lewat form.
        $this->assertSame($operator->id, $survei->surveyor_id);
        $this->assertSame($ssh->id, $survei->ssh_item_id);
        $this->assertEquals(67500, (float) $survei->harga);
    }

    public function test_surveyor_tidak_bisa_dipalsukan_lewat_input_form(): void
    {
        $operator = $this->user('operator@sishd.test');
        $adminSsh = $this->user('adminssh@sishd.test');

        $this->actingAs($operator)->post(route('survei-harga.store'), [
            'uraian_barang' => 'Besi Beton 10 mm',
            'tanggal_survei' => '2026-03-05',
            'harga' => 80000,
            'surveyor_id' => $adminSsh->id,
        ])->assertRedirect(route('survei-harga.index'));

        $this->assertSame($operator->id, PriceSurvey::firstOrFail()->surveyor_id);
    }

    public function test_bukti_survei_tersimpan_sebagai_berkas_dan_baris_evidence(): void
    {
        Storage::fake('public');

        $this->actingAs($this->user('operator@sishd.test'))->post(route('survei-harga.store'), [
            'uraian_barang' => 'Cat Tembok 25 Kg',
            'tanggal_survei' => '2026-03-06',
            'harga' => 450000,
            'jenis_bukti' => 'foto_daftar_harga',
            'bukti' => [UploadedFile::fake()->image('daftar-harga.jpg')],
        ])->assertRedirect(route('survei-harga.index'));

        $evidence = PriceEvidence::firstOrFail();

        $this->assertSame('foto_daftar_harga', $evidence->jenis_bukti);
        Storage::disk('public')->assertExists($evidence->file_path);
    }

    public function test_survei_ditolak_bila_harga_negatif_atau_tanggal_kosong(): void
    {
        $this->actingAs($this->user('operator@sishd.test'))
            ->post(route('survei-harga.store'), ['uraian_barang' => 'Pasir Pasang', 'harga' => -1])
            ->assertSessionHasErrors(['tanggal_survei', 'harga']);

        $this->assertSame(0, PriceSurvey::count());
    }

    public function test_menghapus_survei_ikut_menghapus_berkas_buktinya(): void
    {
        Storage::fake('public');

        $this->actingAs($this->user('operator@sishd.test'))->post(route('survei-harga.store'), [
            'uraian_barang' => 'Keramik 40x40',
            'tanggal_survei' => '2026-03-07',
            'harga' => 55000,
            'bukti' => [UploadedFile::fake()->image('toko.jpg')],
        ]);

        $survei = PriceSurvey::firstOrFail();
        $path = PriceEvidence::firstOrFail()->file_path;

        $this->actingAs($this->user('adminssh@sishd.test'))
            ->delete(route('survei-harga.destroy', $survei))
            ->assertRedirect();

        // Berkas bukti tidak boleh tertinggal jadi sampah di storage setelah datanya dihapus.
        Storage::disk('public')->assertMissing($path);
        $this->assertSame(0, PriceSurvey::count());
        $this->assertSame(0, PriceEvidence::count());
    }

    public function test_operator_tidak_bisa_menghapus_survei_milik_orang_lain(): void
    {
        $pemilik = $this->user('adminssh@sishd.test');
        $survei = $this->buatSurvei(['surveyor_id' => $pemilik->id]);

        // Bukti survei adalah dasar penetapan harga: operator lain tidak boleh menghapusnya
        // hanya dengan menebak URL, meskipun menu Survei Harga memang terbuka untuk role-nya.
        $this->actingAs($this->user('operator@sishd.test'))
            ->delete(route('survei-harga.destroy', $survei))
            ->assertForbidden();

        $this->assertSame(1, PriceSurvey::count());
    }

    public function test_surveyor_boleh_menghapus_survei_buatannya_sendiri(): void
    {
        $operator = $this->user('operator@sishd.test');
        $survei = $this->buatSurvei(['surveyor_id' => $operator->id]);

        $this->actingAs($operator)
            ->delete(route('survei-harga.destroy', $survei))
            ->assertRedirect();

        $this->assertSame(0, PriceSurvey::count());
    }

    public function test_admin_harga_boleh_menghapus_survei_siapa_pun(): void
    {
        $survei = $this->buatSurvei(['surveyor_id' => $this->user('operator@sishd.test')->id]);

        $this->actingAs($this->user('adminssh@sishd.test'))
            ->delete(route('survei-harga.destroy', $survei))
            ->assertRedirect();

        $this->assertSame(0, PriceSurvey::count());
    }

    public function test_daftar_survei_bisa_disaring_per_item_ssh(): void
    {
        $semen = $this->buatSsh('Semen Portland 40 Kg');
        $besi = $this->buatSsh('Besi Beton 10 mm');

        $this->buatSurvei(['ssh_item_id' => $semen->id, 'uraian_barang' => 'Semen Portland 40 Kg']);
        $this->buatSurvei(['ssh_item_id' => $besi->id, 'uraian_barang' => 'Besi Beton 10 mm']);

        $this->actingAs($this->user('adminssh@sishd.test'))
            ->get(route('survei-harga.index', ['ssh_item_id' => $semen->id]))
            ->assertOk()
            ->assertSee('Semen Portland 40 Kg')
            ->assertDontSee('Besi Beton 10 mm');
    }

    public function test_halaman_detail_survei_menampilkan_rekap_harga(): void
    {
        $ssh = $this->buatSsh();
        $survei = $this->buatSurvei(['ssh_item_id' => $ssh->id, 'harga' => 65000]);
        $this->buatSurvei(['ssh_item_id' => $ssh->id, 'harga' => 70000]);

        $this->actingAs($this->user('adminssh@sishd.test'))
            ->get(route('survei-harga.show', $survei))
            ->assertOk();
    }

    // --- Rekap statistik (dasar angka rekomendasi) ---

    public function test_rekap_menghitung_median_untuk_jumlah_data_ganjil(): void
    {
        $rekap = app(PriceSurveyAnalysisService::class)->summarize(collect([70000, 65000, 90000]));

        $this->assertSame(3, $rekap['jumlah']);
        $this->assertEquals(65000, $rekap['min']);
        $this->assertEquals(90000, $rekap['max']);
        $this->assertEquals(70000, $rekap['median']);
        $this->assertEquals(75000, $rekap['rata_rata']);
    }

    public function test_rekap_menghitung_median_sebagai_rata_rata_dua_nilai_tengah_saat_genap(): void
    {
        $rekap = app(PriceSurveyAnalysisService::class)->summarize(collect([60000, 70000, 80000, 100000]));

        $this->assertSame(4, $rekap['jumlah']);
        $this->assertEquals(75000, $rekap['median']);
        $this->assertEquals(77500, $rekap['rata_rata']);
    }

    public function test_harga_rekomendasi_memakai_median_sehingga_tahan_terhadap_harga_nyeleneh(): void
    {
        // Satu penawaran ekstrem (5 juta) menarik rata-rata jauh ke atas; rekomendasi harus tetap
        // di kisaran pasar. Inilah alasan median dipakai, bukan rata-rata.
        $rekap = app(PriceSurveyAnalysisService::class)->summarize(collect([65000, 66000, 67000, 5000000]));

        $this->assertEquals(66500, $rekap['rekomendasi']);
        $this->assertGreaterThan($rekap['rekomendasi'], $rekap['rata_rata']);
    }

    public function test_rekap_tanpa_data_mengembalikan_nol_bukan_error(): void
    {
        $rekap = app(PriceSurveyAnalysisService::class)->summarize(collect([]));

        $this->assertSame(0, $rekap['jumlah']);
        $this->assertEquals(0.0, $rekap['rekomendasi']);
    }

    public function test_rekap_per_item_ssh_hanya_menghitung_survei_item_itu(): void
    {
        $semen = $this->buatSsh('Semen Portland 40 Kg');
        $besi = $this->buatSsh('Besi Beton 10 mm');

        $this->buatSurvei(['ssh_item_id' => $semen->id, 'harga' => 65000]);
        $this->buatSurvei(['ssh_item_id' => $semen->id, 'harga' => 67000]);
        $this->buatSurvei(['ssh_item_id' => $besi->id, 'harga' => 9000000]);

        $rekap = app(PriceSurveyAnalysisService::class)->forSshItem($semen->id);

        $this->assertSame(2, $rekap['jumlah']);
        $this->assertEquals(67000, $rekap['max']);
    }

    // --- Batas akses ---

    public function test_role_tanpa_kewenangan_tidak_bisa_membuka_survei_harga(): void
    {
        foreach (['verifikator@sishd.test', 'pimpinan@sishd.test', 'adminhspk@sishd.test'] as $email) {
            $this->actingAs($this->user($email))
                ->get(route('survei-harga.index'))
                ->assertForbidden();
        }
    }

    public function test_role_tanpa_kewenangan_tidak_bisa_menyimpan_survei_lewat_post_langsung(): void
    {
        $this->actingAs($this->user('verifikator@sishd.test'))->post(route('survei-harga.store'), [
            'uraian_barang' => 'Percobaan Lompat Menu',
            'tanggal_survei' => '2026-03-08',
            'harga' => 1000,
        ])->assertForbidden();

        $this->assertSame(0, PriceSurvey::count());
    }

    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        $this->get(route('survei-harga.index'))->assertRedirect(route('login'));
    }
}
