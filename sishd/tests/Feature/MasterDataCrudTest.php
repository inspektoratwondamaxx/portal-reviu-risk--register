<?php

namespace Tests\Feature;

use App\Models\Asb;
use App\Models\Hspk;
use App\Models\HspkComponent;
use App\Models\PriceHistory;
use App\Models\SbuItem;
use App\Models\SshItem;
use App\Models\TahunAnggaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CRUD master data SSH/SBU/HSPK/ASB (Bab 6-9 & 13 kajian). Modul ini yang dipakai paling sering,
 * jadi yang diuji adalah hal-hal yang diam-diam merusak data kalau salah: kode ganda, perubahan
 * harga yang tidak tercatat riwayatnya, item nonaktif yang tetap muncul di daftar aktif, dan
 * admin satu domain yang bisa menyunting master data domain lain.
 */
class MasterDataCrudTest extends TestCase
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

    private function dataSsh(array $ubah = []): array
    {
        return $ubah + [
            'tahun_anggaran_id' => $this->tahun->id,
            'uraian' => 'Semen Portland 40 Kg',
            'satuan' => 'Zak',
            'harga' => 65000,
        ];
    }

    private function buatSsh(string $uraian = 'Semen Portland 40 Kg', float $harga = 65000): SshItem
    {
        return SshItem::create([
            'kode_barang' => 'SSH-'.fake()->unique()->numerify('#####'),
            'tahun_anggaran_id' => $this->tahun->id,
            'uraian' => $uraian,
            'satuan' => 'Zak',
            'harga' => $harga,
            'status' => 'aktif',
            'is_active' => true,
        ]);
    }

    // --- SSH ---

    public function test_ssh_baru_tersimpan_dengan_kode_otomatis_bila_dikosongkan(): void
    {
        $this->actingAs($this->user('adminssh@sishd.test'))
            ->post(route('admin.ssh.store'), $this->dataSsh())
            ->assertRedirect(route('admin.ssh.index'));

        $item = SshItem::firstOrFail();

        $this->assertMatchesRegularExpression('/^SSH-\d{6}-\d{4}$/', $item->kode_barang);
        $this->assertSame('aktif', $item->status->value);
        $this->assertSame($this->user('adminssh@sishd.test')->id, $item->created_by);
    }

    public function test_ssh_menerima_kode_barang_yang_diisi_manual(): void
    {
        $this->actingAs($this->user('adminssh@sishd.test'))
            ->post(route('admin.ssh.store'), $this->dataSsh(['kode_barang' => 'SSH-MANUAL-01']))
            ->assertRedirect(route('admin.ssh.index'));

        $this->assertSame('SSH-MANUAL-01', SshItem::firstOrFail()->kode_barang);
    }

    public function test_ssh_dengan_kode_barang_ganda_ditolak(): void
    {
        $admin = $this->user('adminssh@sishd.test');
        $this->actingAs($admin)->post(route('admin.ssh.store'), $this->dataSsh(['kode_barang' => 'SSH-DUP-01']));

        $this->actingAs($admin)
            ->post(route('admin.ssh.store'), $this->dataSsh(['kode_barang' => 'SSH-DUP-01', 'uraian' => 'Barang Lain']))
            ->assertSessionHasErrors('kode_barang');

        $this->assertSame(1, SshItem::count());
    }

    public function test_ssh_ditolak_tanpa_uraian_satuan_harga_dan_tahun_anggaran(): void
    {
        $this->actingAs($this->user('adminssh@sishd.test'))
            ->post(route('admin.ssh.store'), [])
            ->assertSessionHasErrors(['uraian', 'satuan', 'harga', 'tahun_anggaran_id']);

        $this->assertSame(0, SshItem::count());
    }

    public function test_perubahan_harga_ssh_tercatat_di_riwayat_beserta_dasar_perubahannya(): void
    {
        $item = $this->buatSsh(harga: 65000);

        $this->actingAs($this->user('adminssh@sishd.test'))->put(route('admin.ssh.update', $item), $this->dataSsh([
            'kode_barang' => $item->kode_barang,
            'harga' => 71500,
            'dasar_perubahan' => 'Hasil survei harga Maret 2026',
        ]))->assertRedirect(route('admin.ssh.index'));

        $riwayat = PriceHistory::where('item_type', 'ssh')->where('item_id', $item->id)->firstOrFail();

        $this->assertEquals(65000, (float) $riwayat->harga_lama);
        $this->assertEquals(71500, (float) $riwayat->harga_baru);
        $this->assertEquals(10.0, (float) $riwayat->persentase);
        $this->assertSame('Hasil survei harga Maret 2026', $riwayat->dasar_perubahan);
        $this->assertSame($this->user('adminssh@sishd.test')->id, $riwayat->user_id);
    }

    public function test_menyunting_ssh_tanpa_mengubah_harga_tidak_membuat_riwayat_palsu(): void
    {
        $item = $this->buatSsh(harga: 65000);

        $this->actingAs($this->user('adminssh@sishd.test'))->put(route('admin.ssh.update', $item), $this->dataSsh([
            'kode_barang' => $item->kode_barang,
            'uraian' => 'Semen Portland 40 Kg (revisi ejaan)',
            'harga' => 65000,
        ]))->assertRedirect(route('admin.ssh.index'));

        $this->assertSame(0, PriceHistory::where('item_id', $item->id)->count());
        $this->assertSame('Semen Portland 40 Kg (revisi ejaan)', $item->fresh()->uraian);
    }

    public function test_ssh_nonaktif_hilang_dari_daftar_tetapi_datanya_tetap_tersimpan(): void
    {
        $item = $this->buatSsh('Barang Akan Dinonaktifkan');
        $admin = $this->user('adminssh@sishd.test');

        $this->actingAs($admin)->post(route('admin.ssh.nonaktifkan', $item))->assertRedirect();

        // Dinonaktifkan, bukan dihapus — histori harga & keterkaitan HSPK harus tetap bisa ditelusuri.
        $this->assertDatabaseHas('ssh_items', ['id' => $item->id, 'status' => 'nonaktif', 'is_active' => false]);

        // Dicek lewat kode barangnya, bukan uraiannya: uraian ikut muncul di pesan notifikasi
        // "SSH ... dinonaktifkan" sehingga tidak bisa membedakan baris tabel dari sekadar notifikasi.
        $this->actingAs($admin)->get(route('admin.ssh.index'))
            ->assertOk()->assertDontSee($item->kode_barang);

        $this->actingAs($admin)->get(route('admin.ssh.index', ['status' => 'nonaktif']))
            ->assertOk()->assertSee($item->kode_barang);
    }

    public function test_ssh_nonaktif_dapat_diaktifkan_kembali(): void
    {
        $item = $this->buatSsh();
        $admin = $this->user('adminssh@sishd.test');

        $this->actingAs($admin)->post(route('admin.ssh.nonaktifkan', $item));
        $this->actingAs($admin)->post(route('admin.ssh.aktifkan', $item))->assertRedirect();

        $this->assertDatabaseHas('ssh_items', ['id' => $item->id, 'status' => 'aktif', 'is_active' => true]);
    }

    public function test_cek_serupa_menemukan_barang_mirip_walau_penulisannya_berbeda(): void
    {
        $this->buatSsh('Semen Portland 40 Kg');

        $this->actingAs($this->user('adminssh@sishd.test'))
            ->postJson(route('admin.ssh.cek-serupa'), ['uraian' => 'semen portland 40kg'])
            ->assertOk()
            ->assertJsonPath('hasil.0.uraian', 'Semen Portland 40 Kg');
    }

    // --- SBU ---

    public function test_sbu_baru_tersimpan_dengan_kode_otomatis(): void
    {
        $this->actingAs($this->user('adminssh@sishd.test'))->post(route('admin.sbu.store'), [
            'kategori' => 'honorarium',
            'uraian' => 'Honorarium Narasumber Eselon II',
            'satuan' => 'OJ',
            'besaran' => 1400000,
            'tahun_anggaran_id' => $this->tahun->id,
        ])->assertRedirect(route('admin.sbu.index'));

        $this->assertMatchesRegularExpression('/^SBU-\d{6}-\d{4}$/', SbuItem::firstOrFail()->kode);
    }

    public function test_sbu_dengan_kategori_di_luar_daftar_ditolak(): void
    {
        $this->actingAs($this->user('adminssh@sishd.test'))->post(route('admin.sbu.store'), [
            'kategori' => 'kategori-karangan',
            'uraian' => 'Honorarium Fiktif',
            'satuan' => 'OJ',
            'besaran' => 1000,
            'tahun_anggaran_id' => $this->tahun->id,
        ])->assertSessionHasErrors('kategori');

        $this->assertSame(0, SbuItem::count());
    }

    // --- HSPK ---

    public function test_hspk_baru_dan_penambahan_komponen_menghitung_ulang_harga_satuan(): void
    {
        $admin = $this->user('adminhspk@sishd.test');
        $semen = $this->buatSsh('Semen Portland 40 Kg', 65000);

        $this->actingAs($admin)->post(route('admin.hspk.store'), [
            'uraian' => 'Pasangan Dinding Bata Merah 1:4',
            'satuan' => 'M2',
            'tahun_anggaran_id' => $this->tahun->id,
        ])->assertRedirect();

        $hspk = Hspk::firstOrFail();
        $this->assertMatchesRegularExpression('/^HSPK-\d{6}-\d{4}$/', $hspk->kode);

        $this->actingAs($admin)->post(route('admin.hspk.komponen.store', $hspk), [
            'komponen_type' => 'material',
            'ssh_item_id' => $semen->id,
            'koefisien' => 2.5,
        ])->assertRedirect();

        // 2,5 zak x Rp65.000 = Rp162.500 — angka HSPK harus hasil hitungan, bukan ketikan manual.
        $this->assertEquals(162500, (float) $hspk->fresh()->harga_satuan);
    }

    public function test_menghapus_komponen_hspk_milik_hspk_lain_ditolak(): void
    {
        $admin = $this->user('adminhspk@sishd.test');
        $semen = $this->buatSsh();

        $hspkA = Hspk::create(['kode' => 'HSPK-A', 'uraian' => 'Pekerjaan A', 'satuan' => 'M2', 'tahun_anggaran_id' => $this->tahun->id, 'status' => 'aktif', 'is_active' => true]);
        $hspkB = Hspk::create(['kode' => 'HSPK-B', 'uraian' => 'Pekerjaan B', 'satuan' => 'M2', 'tahun_anggaran_id' => $this->tahun->id, 'status' => 'aktif', 'is_active' => true]);

        $komponenB = HspkComponent::create([
            'hspk_id' => $hspkB->id, 'komponen_type' => 'material',
            'ssh_item_id' => $semen->id, 'koefisien' => 1, 'urutan' => 1,
        ]);

        // URL ditebak-tebak (komponen milik B dihapus lewat jalur A) harus ditolak di server.
        $this->actingAs($admin)
            ->delete(route('admin.hspk.komponen.destroy', ['hspk' => $hspkA, 'component' => $komponenB]))
            ->assertNotFound();

        $this->assertDatabaseHas('hspk_components', ['id' => $komponenB->id]);
    }

    // --- ASB ---

    public function test_asb_menghitung_hasil_dari_formula_dan_variabelnya(): void
    {
        $admin = $this->user('adminhspk@sishd.test');

        $this->actingAs($admin)->post(route('admin.asb.store'), [
            'nama_kegiatan' => 'Bimbingan Teknis Aparatur',
            'satuan_variabel' => 'Peserta',
            'tahun_anggaran_id' => $this->tahun->id,
        ])->assertRedirect();

        $asb = Asb::firstOrFail();

        $this->actingAs($admin)->post(route('admin.asb.variabel.store', $asb), [
            'kode_variabel' => 'jumlah_peserta', 'label' => 'Jumlah Peserta',
            'nilai' => 40, 'sumber_tipe' => 'manual',
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.asb.variabel.store', $asb), [
            'kode_variabel' => 'biaya_per_peserta', 'label' => 'Biaya per Peserta',
            'nilai' => 250000, 'sumber_tipe' => 'manual',
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.asb.formula.store', $asb), [
            'ekspresi' => '{jumlah_peserta} * {biaya_per_peserta}',
        ])->assertRedirect();

        $this->assertEquals(10000000, (float) $asb->fresh()->hasil_perhitungan);
    }

    public function test_formula_asb_yang_memakai_variabel_belum_terdaftar_ditolak(): void
    {
        $admin = $this->user('adminhspk@sishd.test');
        $asb = Asb::create([
            'kode' => 'ASB-UJI-01', 'nama_kegiatan' => 'Kegiatan Uji',
            'tahun_anggaran_id' => $this->tahun->id, 'status' => 'aktif', 'is_active' => true,
        ]);

        $this->actingAs($admin)->post(route('admin.asb.formula.store', $asb), [
            'ekspresi' => '{variabel_hantu} * 2',
        ])->assertSessionHasErrors('ekspresi');

        $this->assertNull($asb->fresh()->formula);
    }

    public function test_kode_otomatis_tidak_bentrok_dengan_kode_yang_diketik_manual(): void
    {
        $admin = $this->user('adminssh@sishd.test');

        // Petugas mengetik kode dengan format yang sama persis dengan format otomatis aplikasi —
        // hal yang wajar terjadi karena dia menyalin pola dari baris lain. Nomor urut berikutnya
        // tidak boleh menabrak kode ini sampai membuat halaman error dan data batal tersimpan.
        $this->actingAs($admin)->post(route('admin.ssh.store'), $this->dataSsh([
            'kode_barang' => 'SSH-'.now()->format('ymd').'-0002',
            'uraian' => 'Barang Berkode Manual',
        ]))->assertRedirect(route('admin.ssh.index'));

        $this->actingAs($admin)
            ->post(route('admin.ssh.store'), $this->dataSsh(['uraian' => 'Barang Berkode Otomatis']))
            ->assertRedirect(route('admin.ssh.index'));

        $this->assertSame(2, SshItem::count());
        $this->assertSame(2, SshItem::distinct('kode_barang')->count('kode_barang'));
    }

    public function test_kode_sbu_hspk_dan_asb_juga_aman_dari_bentrokan_yang_sama(): void
    {
        $adminSsh = $this->user('adminssh@sishd.test');
        $adminHspk = $this->user('adminhspk@sishd.test');
        $hariIni = now()->format('ymd');

        $this->actingAs($adminSsh)->post(route('admin.sbu.store'), [
            'kode' => "SBU-{$hariIni}-0002", 'kategori' => 'honorarium', 'uraian' => 'Honorarium Manual',
            'satuan' => 'OJ', 'besaran' => 1000, 'tahun_anggaran_id' => $this->tahun->id,
        ])->assertRedirect(route('admin.sbu.index'));
        $this->actingAs($adminSsh)->post(route('admin.sbu.store'), [
            'kategori' => 'honorarium', 'uraian' => 'Honorarium Otomatis',
            'satuan' => 'OJ', 'besaran' => 2000, 'tahun_anggaran_id' => $this->tahun->id,
        ])->assertRedirect(route('admin.sbu.index'));
        $this->assertSame(2, SbuItem::count());

        $this->actingAs($adminHspk)->post(route('admin.hspk.store'), [
            'kode' => "HSPK-{$hariIni}-0002", 'uraian' => 'Pekerjaan Manual',
            'satuan' => 'M2', 'tahun_anggaran_id' => $this->tahun->id,
        ])->assertRedirect();
        $this->actingAs($adminHspk)->post(route('admin.hspk.store'), [
            'uraian' => 'Pekerjaan Otomatis', 'satuan' => 'M2', 'tahun_anggaran_id' => $this->tahun->id,
        ])->assertRedirect();
        $this->assertSame(2, Hspk::count());

        $this->actingAs($adminHspk)->post(route('admin.asb.store'), [
            'kode' => "ASB-{$hariIni}-0002", 'nama_kegiatan' => 'Kegiatan Manual',
            'tahun_anggaran_id' => $this->tahun->id,
        ])->assertRedirect();
        $this->actingAs($adminHspk)->post(route('admin.asb.store'), [
            'nama_kegiatan' => 'Kegiatan Otomatis', 'tahun_anggaran_id' => $this->tahun->id,
        ])->assertRedirect();
        $this->assertSame(2, Asb::count());
    }

    // --- Batas akses antar-domain admin ---

    public function test_admin_ssh_tidak_bisa_menyunting_master_hspk_dan_asb(): void
    {
        $admin = $this->user('adminssh@sishd.test');

        $this->actingAs($admin)->get(route('admin.hspk.index'))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.asb.store'), [
            'nama_kegiatan' => 'Percobaan Lintas Domain',
            'tahun_anggaran_id' => $this->tahun->id,
        ])->assertForbidden();

        $this->assertSame(0, Asb::count());
    }

    public function test_admin_hspk_asb_tidak_bisa_menyunting_master_ssh_dan_sbu(): void
    {
        $admin = $this->user('adminhspk@sishd.test');

        $this->actingAs($admin)->get(route('admin.ssh.index'))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.ssh.store'), $this->dataSsh())->assertForbidden();

        $this->assertSame(0, SshItem::count());
    }

    public function test_operator_opd_tidak_bisa_mengubah_master_data_secara_langsung(): void
    {
        $item = $this->buatSsh();

        // Inti Bab 11 kajian: OPD hanya boleh mengusulkan, tidak menyunting master data.
        $this->actingAs($this->user('operator@sishd.test'))
            ->put(route('admin.ssh.update', $item), $this->dataSsh(['harga' => 1]))
            ->assertForbidden();

        $this->assertEquals(65000, (float) $item->fresh()->harga);
    }
}
