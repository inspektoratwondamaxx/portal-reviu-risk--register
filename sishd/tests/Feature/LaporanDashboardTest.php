<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\PriceHistory;
use App\Models\SbuItem;
use App\Models\SshItem;
use App\Models\TahunAnggaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Laporan, dashboard, dan jejak audit (Bab 18 & 22.2 kajian). Modul ini tidak mengubah data, tetapi
 * justru itu bahayanya: angkanya dipakai sebagai dasar pengambilan keputusan dan bahan pemeriksaan,
 * sehingga angka yang salah tidak menimbulkan pesan galat apa pun — hanya kesimpulan yang keliru.
 * Yang diuji karena itu bukan "halaman terbuka", melainkan apakah angka dan filternya benar.
 */
class LaporanDashboardTest extends TestCase
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

    private function buatSsh(string $kode, float $harga = 65000, ?int $tahunId = null): SshItem
    {
        return SshItem::create([
            'kode_barang' => $kode,
            'tahun_anggaran_id' => $tahunId ?? $this->tahun->id,
            'uraian' => 'Barang '.$kode,
            'satuan' => 'Unit',
            'harga' => $harga,
            'status' => 'aktif',
            'is_active' => true,
        ]);
    }

    // --- Laporan rekap ---

    public function test_rekap_ssh_menjumlahkan_harga_seluruh_item(): void
    {
        $this->buatSsh('SSH-R1', 65000);
        $this->buatSsh('SSH-R2', 35000);

        $this->actingAs($this->user('adminssh@sishd.test'))
            ->get(route('laporan.rekap', ['jenis' => 'ssh']))
            ->assertOk()
            ->assertSee('100.000');
    }

    public function test_rekap_dengan_tahun_yang_tidak_ada_menghasilkan_nol_baris(): void
    {
        $this->buatSsh('SSH-R1', 65000);

        // Kalau filter tahun diam-diam diabaikan, laporan akan menjumlahkan SELURUH tahun di bawah
        // judul satu tahun tertentu — angka yang salah tanpa satu pun pesan galat.
        $this->actingAs($this->user('adminssh@sishd.test'))
            ->get(route('laporan.rekap', ['jenis' => 'ssh', 'tahun' => 2099]))
            ->assertOk()
            ->assertDontSee('65.000');
    }

    public function test_rekap_dengan_tahun_yang_ada_hanya_menghitung_tahun_itu(): void
    {
        $tahunLain = TahunAnggaran::firstOrCreate(['tahun' => 2025]);
        $this->buatSsh('SSH-2026', 65000);
        $this->buatSsh('SSH-2025', 41000, $tahunLain->id);

        $this->actingAs($this->user('adminssh@sishd.test'))
            ->get(route('laporan.rekap', ['jenis' => 'ssh', 'tahun' => 2025]))
            ->assertOk()
            ->assertSee('41.000')
            ->assertDontSee('65.000');
    }

    public function test_rekap_sbu_dikelompokkan_per_kategori(): void
    {
        SbuItem::create([
            'kode' => 'SBU-R1', 'kategori' => 'honorarium', 'uraian' => 'Honor Narasumber',
            'satuan' => 'OJ', 'besaran' => 900000, 'tahun_anggaran_id' => $this->tahun->id,
            'status' => 'aktif', 'is_active' => true,
        ]);

        $this->actingAs($this->user('adminssh@sishd.test'))
            ->get(route('laporan.rekap', ['jenis' => 'sbu']))
            ->assertOk()
            ->assertSee('honorarium');
    }

    public function test_rekap_jenis_di_luar_daftar_ditolak(): void
    {
        $this->actingAs($this->user('adminssh@sishd.test'))
            ->get(route('laporan.rekap', ['jenis' => 'jenis-karangan']))
            ->assertNotFound();
    }

    // --- Laporan perubahan harga ---

    public function test_laporan_perubahan_harga_menampilkan_harga_lama_baru_dan_pelakunya(): void
    {
        $item = $this->buatSsh('SSH-H1', 65000);
        $admin = $this->user('adminssh@sishd.test');

        $this->actingAs($admin)->put(route('admin.ssh.update', $item), [
            'kode_barang' => $item->kode_barang,
            'tahun_anggaran_id' => $this->tahun->id,
            'uraian' => $item->uraian,
            'satuan' => 'Unit',
            'harga' => 71500,
            'dasar_perubahan' => 'Hasil survei Maret 2026',
        ]);

        $this->actingAs($admin)->get(route('laporan.perubahan-harga'))
            ->assertOk()
            ->assertSee('65.000')
            ->assertSee('71.500')
            ->assertSee($admin->name)
            ->assertSee('Hasil survei Maret 2026');
    }

    public function test_laporan_perubahan_harga_dapat_disaring_per_jenis(): void
    {
        PriceHistory::create([
            'item_type' => 'ssh', 'item_id' => $this->buatSsh('SSH-F1')->id,
            'harga_lama' => 10000, 'harga_baru' => 12000, 'persentase' => 20,
            'dasar_perubahan' => 'Perubahan SSH', 'tanggal' => '2026-03-01',
        ]);
        PriceHistory::create([
            'item_type' => 'sbu', 'item_id' => 1,
            'harga_lama' => 50000, 'harga_baru' => 55000, 'persentase' => 10,
            'dasar_perubahan' => 'Perubahan SBU', 'tanggal' => '2026-03-02',
        ]);

        $this->actingAs($this->user('adminssh@sishd.test'))
            ->get(route('laporan.perubahan-harga', ['jenis' => 'ssh']))
            ->assertOk()
            ->assertSee('Perubahan SSH')
            ->assertDontSee('Perubahan SBU');
    }

    public function test_laporan_perubahan_harga_dapat_disaring_per_rentang_tanggal(): void
    {
        foreach ([['2026-01-15', 'Perubahan Januari'], ['2026-03-20', 'Perubahan Maret']] as [$tanggal, $dasar]) {
            PriceHistory::create([
                'item_type' => 'ssh', 'item_id' => $this->buatSsh('SSH-'.$tanggal)->id,
                'harga_lama' => 10000, 'harga_baru' => 12000, 'persentase' => 20,
                'dasar_perubahan' => $dasar, 'tanggal' => $tanggal,
            ]);
        }

        $this->actingAs($this->user('adminssh@sishd.test'))
            ->get(route('laporan.perubahan-harga', ['dari' => '2026-03-01', 'sampai' => '2026-03-31']))
            ->assertOk()
            ->assertSee('Perubahan Maret')
            ->assertDontSee('Perubahan Januari');
    }

    // --- Dashboard ---

    public function test_dashboard_menghitung_hanya_data_aktif(): void
    {
        $this->buatSsh('SSH-D1');
        $this->buatSsh('SSH-D2');
        $nonaktif = $this->buatSsh('SSH-D3');
        $nonaktif->forceFill(['status' => 'nonaktif', 'is_active' => false])->save();

        // Angka ringkasan dashboard sering dipakai sebagai "berapa data kita" — item nonaktif tidak
        // boleh ikut terhitung, kalau tidak angkanya menggelembung tanpa disadari.
        $this->actingAs($this->user('adminssh@sishd.test'))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('counts', fn (array $counts) => $counts['ssh'] === 2);
    }

    public function test_dashboard_operator_hanya_menghitung_usulan_opd_sendiri(): void
    {
        $operator = $this->user('operator@sishd.test');
        $opdLain = \App\Models\Opd::where('id', '!=', $operator->opd_id)->firstOrFail();

        $buatUsulan = fn (int $opdId) => app(\App\Services\ProposalWorkflowService::class)->createProposal(
            opdId: $opdId, jenisUsulan: 'ssh', tipePerubahan: 'baru', alasan: 'Uji dashboard.',
            items: [['data_usulan' => ['uraian' => 'Barang', 'satuan' => 'Unit', 'harga' => 1000, 'tahun_anggaran_id' => $this->tahun->id]]],
            creator: $operator,
        );
        $buatUsulan($operator->opd_id);
        $buatUsulan($opdLain->id);

        $this->actingAs($operator)->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('proposalStats', fn (array $stats) => $stats['baru'] === 1);

        // Super Admin melihat seluruh OPD.
        $this->actingAs($this->user('inspektoratwondamaxx@gmail.com'))->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('proposalStats', fn (array $stats) => $stats['baru'] === 2);
    }

    public function test_dashboard_tanpa_data_tidak_membagi_dengan_nol(): void
    {
        $this->actingAs($this->user('adminssh@sishd.test'))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('persentaseJenis', fn (array $p) => $p['SSH'] === 0.0);
    }

    public function test_dashboard_dapat_dibuka_seluruh_role_termasuk_pimpinan(): void
    {
        foreach (['inspektoratwondamaxx@gmail.com', 'adminssh@sishd.test', 'operator@sishd.test',
            'verifikator@sishd.test', 'pimpinan@sishd.test'] as $email) {
            $this->actingAs($this->user($email))->get(route('dashboard'))->assertOk();
        }
    }

    // --- Jejak audit ---

    public function test_perubahan_master_data_tercatat_di_jejak_audit(): void
    {
        $item = $this->buatSsh('SSH-A1', 65000);
        $admin = $this->user('adminssh@sishd.test');

        $this->actingAs($admin)->put(route('admin.ssh.update', $item), [
            'kode_barang' => $item->kode_barang,
            'tahun_anggaran_id' => $this->tahun->id,
            'uraian' => 'Nama Barang Setelah Diubah',
            'satuan' => 'Unit',
            'harga' => 65000,
        ]);

        $log = AuditLog::where('model_type', SshItem::class)->where('action', 'updated')->firstOrFail();

        // Nilai sebelum & sesudah harus keduanya tersimpan — inti gunanya jejak audit bagi pemeriksa.
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('Barang SSH-A1', $log->data_sebelum['uraian']);
        $this->assertSame('Nama Barang Setelah Diubah', $log->data_sesudah['uraian']);
    }

    public function test_menyimpan_tanpa_perubahan_tidak_membuat_catatan_audit_kosong(): void
    {
        $item = $this->buatSsh('SSH-A2', 65000);
        $admin = $this->user('adminssh@sishd.test');
        $sebelum = AuditLog::where('model_type', SshItem::class)->where('action', 'updated')->count();

        $this->actingAs($admin)->put(route('admin.ssh.update', $item), [
            'kode_barang' => $item->kode_barang,
            'tahun_anggaran_id' => $this->tahun->id,
            'uraian' => $item->uraian,
            'satuan' => 'Unit',
            'harga' => 65000,
        ]);

        $this->assertSame($sebelum, AuditLog::where('model_type', SshItem::class)->where('action', 'updated')->count());
    }

    public function test_jejak_audit_hanya_bisa_dibaca_tanpa_jalur_ubah_atau_hapus(): void
    {
        $this->actingAs($this->user('inspektoratwondamaxx@gmail.com'))
            ->get(route('sistem.audit-log.index'))
            ->assertOk();

        // Tidak ada rute ubah/hapus audit log sama sekali — bukan sekadar disembunyikan di tampilan.
        $rutes = collect(\Illuminate\Support\Facades\Route::getRoutes())
            ->filter(fn ($r) => str_contains($r->uri(), 'audit-log'))
            ->flatMap(fn ($r) => $r->methods())
            ->unique()->values()->all();

        $this->assertEqualsCanonicalizing(['GET', 'HEAD'], $rutes);
    }

    public function test_jejak_audit_dapat_disaring_per_aksi(): void
    {
        $admin = $this->user('inspektoratwondamaxx@gmail.com');
        $this->actingAs($admin);
        $item = $this->buatSsh('SSH-A3');
        $item->forceFill(['harga' => 70000])->save();

        $this->get(route('sistem.audit-log.index', ['aksi' => 'created']))
            ->assertOk()
            ->assertViewHas('logs', fn ($logs) => $logs->every(fn ($l) => $l->action === 'created'));
    }

    // --- Batas akses ---

    public function test_operator_dan_pimpinan_tidak_bisa_membuka_laporan(): void
    {
        foreach (['operator@sishd.test', 'pimpinan@sishd.test'] as $email) {
            $this->actingAs($this->user($email))
                ->get(route('laporan.rekap', ['jenis' => 'ssh']))
                ->assertForbidden();

            $this->actingAs($this->user($email))
                ->get(route('laporan.perubahan-harga'))
                ->assertForbidden();
        }
    }

    public function test_hanya_super_admin_yang_bisa_membuka_jejak_audit(): void
    {
        foreach (['adminssh@sishd.test', 'verifikator@sishd.test', 'operator@sishd.test', 'pimpinan@sishd.test'] as $email) {
            $this->actingAs($this->user($email))
                ->get(route('sistem.audit-log.index'))
                ->assertForbidden();
        }
    }

    public function test_tamu_tidak_bisa_membuka_laporan_maupun_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('laporan.perubahan-harga'))->assertRedirect(route('login'));
        $this->get(route('sistem.audit-log.index'))->assertRedirect(route('login'));
    }
}
