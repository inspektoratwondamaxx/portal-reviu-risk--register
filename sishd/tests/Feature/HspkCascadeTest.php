<?php

namespace Tests\Feature;

use App\Models\Hspk;
use App\Models\HspkComponent;
use App\Models\SbuItem;
use App\Models\SshItem;
use App\Models\TahunAnggaran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kaskade harga (Bab 8 & 20 kajian): HSPK adalah harga turunan, bukan angka yang diketik manual —
 * begitu harga komponen SSH/SBU berubah, HSPK yang memakainya harus ikut berubah sendiri.
 */
class HspkCascadeTest extends TestCase
{
    use RefreshDatabase;

    private TahunAnggaran $tahun;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\TahunAnggaranSeeder::class);
        $this->tahun = TahunAnggaran::aktif();
    }

    private function buatSsh(string $uraian, float $harga, string $satuan = 'Zak'): SshItem
    {
        return SshItem::create([
            'kode_barang' => 'SSH-'.fake()->unique()->numerify('#####'),
            'tahun_anggaran_id' => $this->tahun->id,
            'uraian' => $uraian,
            'satuan' => $satuan,
            'harga' => $harga,
            'status' => 'aktif',
            'is_active' => true,
        ]);
    }

    private function buatSbu(string $uraian, float $besaran, string $satuan = 'OH'): SbuItem
    {
        return SbuItem::create([
            'kode' => 'SBU-'.fake()->unique()->numerify('#####'),
            'tahun_anggaran_id' => $this->tahun->id,
            'kategori' => 'lainnya',
            'uraian' => $uraian,
            'satuan' => $satuan,
            'besaran' => $besaran,
            'status' => 'aktif',
            'is_active' => true,
        ]);
    }

    private function buatHspk(): Hspk
    {
        return Hspk::create([
            'kode' => 'HSPK-'.fake()->unique()->numerify('#####'),
            'tahun_anggaran_id' => $this->tahun->id,
            'uraian' => 'Pekerjaan Beton Uji',
            'jenis_pekerjaan' => 'Struktur Beton',
            'satuan' => 'M3',
            'harga_satuan' => 0,
            'status' => 'aktif',
            'is_active' => true,
        ]);
    }

    /**
     * Regresi: harga komponen sempat diambil berdasarkan komponen_type, sehingga komponen
     * "peralatan" yang bersumber dari SBU (mis. sewa molen) tidak pernah terisi dan tetap Rp 0.
     * Sumber harga harus ditentukan dari foreign key yang terisi, apa pun jenis komponennya.
     */
    public function test_komponen_peralatan_dari_sbu_ikut_terhitung(): void
    {
        $semen = $this->buatSsh('Semen Portland', 82_000);
        $molen = $this->buatSbu('Sewa Mixer Molen', 25_000, 'Jam');
        $hspk = $this->buatHspk();

        HspkComponent::create([
            'hspk_id' => $hspk->id, 'komponen_type' => 'material', 'ssh_item_id' => $semen->id,
            'koefisien' => 2, 'satuan' => 'Zak', 'harga_satuan' => 0, 'subtotal' => 0, 'urutan' => 1,
        ]);
        HspkComponent::create([
            'hspk_id' => $hspk->id, 'komponen_type' => 'peralatan', 'sbu_item_id' => $molen->id,
            'koefisien' => 4, 'satuan' => 'Jam', 'harga_satuan' => 0, 'subtotal' => 0, 'urutan' => 2,
        ]);

        app(\App\Services\HspkCalculationService::class)->recalculate($hspk);

        // (2 x 82.000) + (4 x 25.000) = 264.000
        $this->assertEquals(264_000, $hspk->fresh()->harga_satuan);

        $komponenPeralatan = $hspk->components()->where('komponen_type', 'peralatan')->first();
        $this->assertEquals(25_000, $komponenPeralatan->harga_satuan, 'Komponen peralatan dari SBU tidak boleh macet di 0.');
        $this->assertEquals(100_000, $komponenPeralatan->subtotal);
    }

    public function test_perubahan_harga_ssh_otomatis_menghitung_ulang_hspk(): void
    {
        $semen = $this->buatSsh('Semen Portland', 82_000);
        $hspk = $this->buatHspk();

        HspkComponent::create([
            'hspk_id' => $hspk->id, 'komponen_type' => 'material', 'ssh_item_id' => $semen->id,
            'koefisien' => 10, 'satuan' => 'Zak', 'harga_satuan' => 0, 'subtotal' => 0, 'urutan' => 1,
        ]);

        app(\App\Services\HspkCalculationService::class)->recalculate($hspk);
        $this->assertEquals(820_000, $hspk->fresh()->harga_satuan);

        // Harga semen naik -> HSPK harus ikut naik tanpa dihitung ulang secara manual.
        $semen->harga = 90_000;
        $semen->save();

        $this->assertEquals(900_000, $hspk->fresh()->harga_satuan, 'HSPK harus mengikuti perubahan harga SSH komponennya.');
    }

    public function test_perubahan_harga_dicatat_di_riwayat_dan_analisis(): void
    {
        $semen = $this->buatSsh('Semen Portland', 100_000);
        $hspk = $this->buatHspk();

        HspkComponent::create([
            'hspk_id' => $hspk->id, 'komponen_type' => 'material', 'ssh_item_id' => $semen->id,
            'koefisien' => 1, 'satuan' => 'Zak', 'harga_satuan' => 0, 'subtotal' => 0, 'urutan' => 1,
        ]);
        app(\App\Services\HspkCalculationService::class)->recalculate($hspk);

        $semen->harga = 120_000;
        $semen->save();

        $this->assertDatabaseHas('price_histories', [
            'item_type' => 'ssh',
            'item_id' => $semen->id,
            'harga_baru' => 120_000,
        ]);

        $analisis = $hspk->analysis()->latest('id')->first();
        $this->assertNotNull($analisis, 'Perhitungan ulang HSPK harus meninggalkan jejak audit.');
        $this->assertEquals(120_000, $analisis->harga_sesudah);
        $this->assertEquals(100_000, $analisis->harga_sebelum);
    }
}
