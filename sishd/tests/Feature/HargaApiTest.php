<?php

namespace Tests\Feature;

use App\Models\SshItem;
use App\Models\TahunAnggaran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API publik baca-saja /api/v1 (fondasi integrasi SIPD Level 2). Yang dikunci di sini: API hanya
 * boleh menyajikan data yang memang sudah dipublikasikan, dan filter yang diminta klien harus
 * benar-benar diterapkan — termasuk saat filter itu tidak cocok dengan data apa pun.
 */
class HargaApiTest extends TestCase
{
    use RefreshDatabase;

    private TahunAnggaran $tahun;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\TahunAnggaranSeeder::class);
        $this->tahun = TahunAnggaran::aktif();
    }

    private function buatSsh(array $override = []): SshItem
    {
        return SshItem::create(array_merge([
            'kode_barang' => 'TEST-'.fake()->unique()->numerify('####'),
            'tahun_anggaran_id' => $this->tahun->id,
            'uraian' => 'Semen Portland 50 Kg',
            'satuan' => 'Zak',
            'harga' => 82000,
            'status' => 'aktif',
            'is_active' => true,
        ], $override));
    }

    public function test_daftar_ssh_hanya_menampilkan_item_aktif(): void
    {
        $aktif = $this->buatSsh(['uraian' => 'Besi Beton Polos']);
        $nonaktif = $this->buatSsh(['uraian' => 'Barang Nonaktif', 'status' => 'nonaktif', 'is_active' => false]);

        $response = $this->getJson('/api/v1/ssh')->assertOk();

        $kode = array_column($response->json('data'), 'kode_barang');
        $this->assertContains($aktif->kode_barang, $kode);
        $this->assertNotContains($nonaktif->kode_barang, $kode, 'Item nonaktif tidak boleh bocor lewat API publik.');
    }

    public function test_detail_ssh_diakses_dengan_kode_barang(): void
    {
        $item = $this->buatSsh(['kode_barang' => 'TEST-1234', 'harga' => 99500]);

        $this->getJson('/api/v1/ssh/TEST-1234')
            ->assertOk()
            ->assertJsonPath('data.kode_barang', 'TEST-1234')
            ->assertJsonPath('data.uraian', $item->uraian)
            ->assertJsonPath('data.harga', 99500);
    }

    public function test_detail_menolak_item_nonaktif_dan_kode_tak_dikenal(): void
    {
        $this->buatSsh(['kode_barang' => 'TEST-NONAKTIF', 'status' => 'nonaktif', 'is_active' => false]);

        $this->getJson('/api/v1/ssh/TEST-NONAKTIF')->assertNotFound();
        $this->getJson('/api/v1/ssh/TIDAK-ADA')->assertNotFound();
    }

    /**
     * Regresi: filter tahun yang tidak cocok dengan tahun anggaran mana pun sempat diam-diam
     * diabaikan (menampilkan SEMUA tahun) karena "tidak difilter" dan "difilter tapi tak ketemu"
     * sama-sama menghasilkan null. Klien API menyaring secara programatik, jadi ini harus 0 baris.
     */
    public function test_filter_tahun_yang_tidak_ada_mengembalikan_kosong(): void
    {
        $this->buatSsh();

        $this->getJson('/api/v1/ssh?tahun=2099')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/ssh?tahun='.$this->tahun->tahun)->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/ssh')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_pencarian_dan_filter_kode(): void
    {
        $this->buatSsh(['kode_barang' => 'AAA-0001', 'uraian' => 'Cat Tembok Putih']);
        $this->buatSsh(['kode_barang' => 'BBB-0002', 'uraian' => 'Pasir Pasang']);

        $this->getJson('/api/v1/ssh?q=Cat')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.kode_barang', 'AAA-0001');

        $this->getJson('/api/v1/ssh?kode=BBB')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.kode_barang', 'BBB-0002');
    }

    public function test_per_page_dibatasi_maksimal_seratus(): void
    {
        $this->buatSsh();

        $this->getJson('/api/v1/ssh?per_page=9999')->assertOk()->assertJsonPath('meta.per_page', 100);
        $this->getJson('/api/v1/ssh?per_page=0')->assertOk()->assertJsonPath('meta.per_page', 1);
    }

    public function test_ringkasan_menghitung_hanya_data_aktif(): void
    {
        $this->buatSsh();
        $this->buatSsh(['status' => 'nonaktif', 'is_active' => false]);

        $this->getJson('/api/v1/ringkasan')
            ->assertOk()
            ->assertJsonPath('data.ssh', 1)
            ->assertJsonPath('data.tahun_aktif', $this->tahun->tahun);
    }

    public function test_api_tidak_memerlukan_login(): void
    {
        $this->buatSsh();

        $this->assertGuest();
        $this->getJson('/api/v1/ssh')->assertOk();
    }
}
