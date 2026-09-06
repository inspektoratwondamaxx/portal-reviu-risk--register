<?php

namespace Tests\Feature;

use App\Models\AccountCode;
use App\Models\AssetCode;
use App\Models\CodeMapping;
use App\Models\SipdCode;
use App\Models\User;
use App\Services\CodeMappingValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pemetaan Kode Aset -> Kode Rekening -> Kode SIPD (Bab 10 kajian) — inti persoalan yang membuat
 * data harga antar-OPD tidak bisa disatukan. Yang diuji di sini adalah bahwa statusnya benar-benar
 * dihitung ulang dari keadaan data terkini (valid / belum ada rekening / duplikasi / belum
 * dipetakan), bukan status yang ditulis sekali lalu basi ketika datanya berubah.
 */
class PemetaanKodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\OpdSeeder::class);
        $this->seed(\Database\Seeders\TahunAnggaranSeeder::class);
        $this->seed(\Database\Seeders\UserSeeder::class);
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function kodeAset(string $kode = '1.3.2.05.01.01.001', string $nama = 'Semen'): AssetCode
    {
        return AssetCode::create(['kode' => $kode, 'nama' => $nama, 'is_active' => true]);
    }

    private function kodeRekening(string $kode = '5.1.02.01.01.0026'): AccountCode
    {
        return AccountCode::create(['kode' => $kode, 'uraian' => 'Belanja Bahan Bangunan', 'is_active' => true]);
    }

    // --- Perekaman mapping & perhitungan status ---

    public function test_mapping_lengkap_berstatus_valid(): void
    {
        $aset = $this->kodeAset();
        $rekening = $this->kodeRekening();
        $sipd = SipdCode::create(['kode' => 'SIPD-001', 'uraian' => 'Bahan Bangunan', 'tipe' => 'ssh', 'is_active' => true]);

        $this->actingAs($this->user('adminssh@sishd.test'))->post(route('admin.mapping.store'), [
            'asset_code_id' => $aset->id,
            'account_code_id' => $rekening->id,
            'sipd_code_id' => $sipd->id,
        ])->assertRedirect(route('admin.mapping.index'));

        $mapping = CodeMapping::firstOrFail();

        $this->assertSame('valid', $mapping->status->value);
        // checked_at membuktikan statusnya hasil hitung ulang, bukan nilai default dari migrasi.
        $this->assertNotNull($mapping->checked_at);
    }

    public function test_mapping_tanpa_kode_rekening_berstatus_belum_rekening(): void
    {
        $aset = $this->kodeAset();

        $this->actingAs($this->user('adminssh@sishd.test'))
            ->post(route('admin.mapping.store'), ['asset_code_id' => $aset->id])
            ->assertRedirect(route('admin.mapping.index'));

        $this->assertSame('belum_rekening', CodeMapping::firstOrFail()->status->value);
    }

    public function test_dua_mapping_untuk_kode_aset_yang_sama_ditandai_duplikasi(): void
    {
        $aset = $this->kodeAset();
        $rekening = $this->kodeRekening();
        $rekeningLain = $this->kodeRekening('5.1.02.01.01.0027');

        $admin = $this->user('adminssh@sishd.test');
        $this->actingAs($admin)->post(route('admin.mapping.store'), [
            'asset_code_id' => $aset->id, 'account_code_id' => $rekening->id,
        ]);
        $this->actingAs($admin)->post(route('admin.mapping.store'), [
            'asset_code_id' => $aset->id, 'account_code_id' => $rekeningLain->id,
        ]);

        // Kedua barisnya ditandai — bukan hanya yang terakhir masuk — supaya keduanya muncul di
        // daftar yang harus dibereskan petugas.
        $this->assertSame(2, CodeMapping::where('status', 'duplikasi')->count());
    }

    public function test_kode_aset_yang_belum_pernah_dipetakan_dihitung_sebagai_tidak_ditemukan(): void
    {
        $this->kodeAset('1.3.2.05.01.01.001', 'Semen');
        $this->kodeAset('1.3.2.05.01.01.002', 'Besi Beton');

        $ringkasan = app(CodeMappingValidationService::class)->validateAll();

        $this->assertSame(2, $ringkasan['tidak_ditemukan']);
        $this->assertSame(0, $ringkasan['valid']);
    }

    public function test_kode_aset_nonaktif_tidak_ikut_dihitung_sebagai_belum_dipetakan(): void
    {
        $this->kodeAset()->update(['is_active' => false]);

        $this->assertSame(0, app(CodeMappingValidationService::class)->validateAll()['tidak_ditemukan']);
    }

    public function test_melengkapi_kode_rekening_mengubah_status_dari_belum_rekening_menjadi_valid(): void
    {
        $aset = $this->kodeAset();
        $rekening = $this->kodeRekening();
        $admin = $this->user('adminssh@sishd.test');

        $this->actingAs($admin)->post(route('admin.mapping.store'), ['asset_code_id' => $aset->id]);
        $mapping = CodeMapping::firstOrFail();
        $this->assertSame('belum_rekening', $mapping->status->value);

        $this->actingAs($admin)->put(route('admin.mapping.update', $mapping), [
            'account_code_id' => $rekening->id,
            'catatan' => 'Dilengkapi setelah konfirmasi BPKAD.',
        ])->assertRedirect();

        $this->assertSame('valid', $mapping->fresh()->status->value);
    }

    public function test_menghapus_satu_mapping_duplikat_mengembalikan_sisanya_menjadi_valid(): void
    {
        $aset = $this->kodeAset();
        $rekening = $this->kodeRekening();
        $admin = $this->user('adminssh@sishd.test');

        $this->actingAs($admin)->post(route('admin.mapping.store'), ['asset_code_id' => $aset->id, 'account_code_id' => $rekening->id]);
        $this->actingAs($admin)->post(route('admin.mapping.store'), ['asset_code_id' => $aset->id, 'account_code_id' => $rekening->id]);
        $this->assertSame(2, CodeMapping::where('status', 'duplikasi')->count());

        $this->actingAs($admin)
            ->delete(route('admin.mapping.destroy', CodeMapping::orderByDesc('id')->first()))
            ->assertRedirect();

        // Status sisa mapping harus ikut pulih — bukti status dihitung ulang setelah penghapusan.
        $this->assertSame('valid', CodeMapping::firstOrFail()->status->value);
    }

    public function test_ringkasan_validasi_ditampilkan_kembali_ke_petugas(): void
    {
        $aset = $this->kodeAset();
        $this->kodeAset('1.3.2.05.01.01.002', 'Besi Beton');
        CodeMapping::create(['asset_code_id' => $aset->id, 'account_code_id' => $this->kodeRekening()->id]);

        $this->actingAs($this->user('adminssh@sishd.test'))
            ->post(route('admin.mapping.validasi'))
            ->assertRedirect()
            ->assertSessionHas('status', 'Validasi selesai: 1 valid, 0 belum ada rekening, 0 duplikasi, 1 kode aset belum dipetakan.');
    }

    // --- Cek kode cepat (dipakai form pencarian di halaman Mapping) ---

    public function test_cek_kode_mengembalikan_status_valid_untuk_kode_yang_sudah_dipetakan(): void
    {
        $aset = $this->kodeAset();
        CodeMapping::create(['asset_code_id' => $aset->id, 'account_code_id' => $this->kodeRekening()->id]);

        $this->actingAs($this->user('adminssh@sishd.test'))
            ->getJson(route('admin.mapping.cek-kode', ['kode' => $aset->kode]))
            ->assertOk()
            ->assertJsonPath('status', 'valid')
            ->assertJsonPath('label', 'Kode valid');
    }

    public function test_cek_kode_yang_tidak_terdaftar_mengembalikan_tidak_ditemukan(): void
    {
        $this->actingAs($this->user('adminssh@sishd.test'))
            ->getJson(route('admin.mapping.cek-kode', ['kode' => '9.9.9.99.99.99.999']))
            ->assertOk()
            ->assertJsonPath('status', 'tidak_ditemukan')
            ->assertJsonPath('asset_code', null);
    }

    public function test_cek_kode_terdaftar_tetapi_belum_dipetakan_juga_tidak_ditemukan(): void
    {
        $aset = $this->kodeAset();

        $this->actingAs($this->user('adminssh@sishd.test'))
            ->getJson(route('admin.mapping.cek-kode', ['kode' => $aset->kode]))
            ->assertOk()
            ->assertJsonPath('status', 'tidak_ditemukan')
            ->assertJsonPath('asset_code.kode', $aset->kode);
    }

    public function test_cek_kode_menandai_duplikasi(): void
    {
        $aset = $this->kodeAset();
        $rekening = $this->kodeRekening();
        CodeMapping::create(['asset_code_id' => $aset->id, 'account_code_id' => $rekening->id]);
        CodeMapping::create(['asset_code_id' => $aset->id, 'account_code_id' => $rekening->id]);

        $this->actingAs($this->user('adminssh@sishd.test'))
            ->getJson(route('admin.mapping.cek-kode', ['kode' => $aset->kode]))
            ->assertOk()
            ->assertJsonPath('status', 'duplikasi');
    }

    public function test_cek_kode_wajib_mengirim_parameter_kode(): void
    {
        $this->actingAs($this->user('adminssh@sishd.test'))
            ->getJson(route('admin.mapping.cek-kode'))
            ->assertStatus(422);
    }

    // --- Validasi input & batas akses ---

    public function test_mapping_ditolak_bila_kode_aset_tidak_ada(): void
    {
        $this->actingAs($this->user('adminssh@sishd.test'))
            ->post(route('admin.mapping.store'), ['asset_code_id' => 999999])
            ->assertSessionHasErrors('asset_code_id');

        $this->assertSame(0, CodeMapping::count());
    }

    public function test_role_tanpa_kewenangan_tidak_bisa_membuka_atau_mengubah_mapping(): void
    {
        foreach (['operator@sishd.test', 'verifikator@sishd.test', 'adminhspk@sishd.test', 'pimpinan@sishd.test'] as $email) {
            $this->actingAs($this->user($email))
                ->get(route('admin.mapping.index'))
                ->assertForbidden();
        }

        $aset = $this->kodeAset();
        $this->actingAs($this->user('operator@sishd.test'))
            ->post(route('admin.mapping.store'), ['asset_code_id' => $aset->id])
            ->assertForbidden();

        $this->assertSame(0, CodeMapping::count());
    }
}
