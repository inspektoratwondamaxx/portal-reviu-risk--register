<?php

namespace Tests\Feature;

use App\Models\Asb;
use App\Models\Hspk;
use App\Models\Proposal;
use App\Models\SbuItem;
use App\Models\SshItem;
use App\Models\TahunAnggaran;
use App\Models\User;
use App\Services\ProposalWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Form usulan OPD per jenis (SSH/SBU/HSPK/ASB). Regresi penting: form ini pernah mengirim susunan
 * field yang sama untuk semua jenis, sehingga harga SBU hilang diam-diam (dikirim sebagai `harga`
 * padahal yang dipakai `besaran`) dan `nama_kegiatan` ASB — kolom NOT NULL — tidak pernah terisi
 * sehingga usulan baru meledak saat disetujui, bukan saat diajukan. Karena itu tiap jenis di sini
 * diuji sampai benar-benar termaterialisasi ke tabel master, bukan berhenti di "usulan tersimpan".
 */
class UsulanOpdTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\OpdSeeder::class);
        $this->seed(\Database\Seeders\TahunAnggaranSeeder::class);
        $this->seed(\Database\Seeders\UserSeeder::class);

        $this->operator = User::where('email', 'operator@sishd.test')->firstOrFail();
    }

    /** Ajukan usulan lewat HTTP persis seperti form mengirimnya. */
    private function ajukan(array $data): Proposal
    {
        $response = $this->actingAs($this->operator)->post(route('opd.usulan.store'), $data);
        $response->assertSessionHasNoErrors();

        return Proposal::latest('id')->firstOrFail();
    }

    /** Loloskan usulan melewati seluruh rantai approval sampai jadi data master. */
    private function setujuiSampaiTuntas(Proposal $proposal): Proposal
    {
        foreach (['verifikator@sishd.test', 'timstandarharga@sishd.test', 'pejabat@sishd.test'] as $email) {
            $proposal = app(ProposalWorkflowService::class)->review(
                $proposal,
                User::where('email', $email)->firstOrFail(),
                'setuju',
                'Disetujui (uji otomatis).'
            );
        }

        return $proposal;
    }

    public function test_usulan_ssh_menyimpan_harga_sampai_ke_master(): void
    {
        $proposal = $this->ajukan([
            'jenis_usulan' => 'ssh',
            'tipe_perubahan' => 'baru',
            'alasan_usulan' => 'Kebutuhan pemeliharaan jalan.',
            'uraian' => 'Semen Portland 50 Kg',
            'satuan' => 'Zak',
            'harga' => 82000,
        ]);

        $this->assertSame(82000, (int) $proposal->items->first()->data_usulan['harga']);

        $this->setujuiSampaiTuntas($proposal);

        $item = SshItem::where('uraian', 'Semen Portland 50 Kg')->first();
        $this->assertNotNull($item, 'Usulan SSH yang disetujui harus muncul di master SSH.');
        $this->assertEquals(82000, $item->harga);
        $this->assertSame($this->operator->opd_id, $item->opd_id);
    }

    /** Regresi: harga SBU dipetakan ke kolom `besaran`, bukan `harga` — dulu senyap jadi 0. */
    public function test_usulan_sbu_menyimpan_besaran_bukan_nol(): void
    {
        $proposal = $this->ajukan([
            'jenis_usulan' => 'sbu',
            'tipe_perubahan' => 'baru',
            'alasan_usulan' => 'Penyesuaian honorarium narasumber.',
            'uraian' => 'Honorarium Narasumber Uji',
            'kategori' => 'honorarium',
            'satuan' => 'OJ',
            'besaran' => 900000,
        ]);

        $this->assertEquals(900000, $proposal->items->first()->data_usulan['besaran']);

        $this->setujuiSampaiTuntas($proposal);

        $item = SbuItem::where('uraian', 'Honorarium Narasumber Uji')->first();
        $this->assertNotNull($item);
        $this->assertEquals(900000, $item->besaran, 'Besaran SBU tidak boleh hilang menjadi 0.');
    }

    /** Regresi: `nama_kegiatan` ASB adalah kolom NOT NULL — kalau kosong, gagalnya baru saat disetujui. */
    public function test_usulan_asb_menyimpan_nama_kegiatan(): void
    {
        $proposal = $this->ajukan([
            'jenis_usulan' => 'asb',
            'tipe_perubahan' => 'baru',
            'alasan_usulan' => 'Penambahan ASB pembangunan gedung.',
            'nama_kegiatan' => 'Pembangunan Gedung Uji',
            'kelompok_kegiatan' => 'Belanja Modal Gedung',
            'satuan_variabel' => 'M2',
        ]);

        $this->assertSame('Pembangunan Gedung Uji', $proposal->items->first()->data_usulan['nama_kegiatan']);

        $this->setujuiSampaiTuntas($proposal);

        $this->assertNotNull(
            Asb::where('nama_kegiatan', 'Pembangunan Gedung Uji')->first(),
            'Usulan ASB harus bisa disetujui tanpa melanggar NOT NULL pada nama_kegiatan.'
        );
    }

    public function test_usulan_hspk_tersimpan_dan_termaterialisasi(): void
    {
        $proposal = $this->ajukan([
            'jenis_usulan' => 'hspk',
            'tipe_perubahan' => 'baru',
            'alasan_usulan' => 'Penambahan analisa pekerjaan baru.',
            'uraian' => 'Pekerjaan Plesteran Uji',
            'jenis_pekerjaan' => 'Pekerjaan Dinding',
            'satuan' => 'M2',
        ]);

        $this->setujuiSampaiTuntas($proposal);

        $this->assertNotNull(Hspk::where('uraian', 'Pekerjaan Plesteran Uji')->first());
    }

    // --- Validasi input ---

    public function test_menolak_usulan_ssh_tanpa_harga(): void
    {
        $this->actingAs($this->operator)
            ->post(route('opd.usulan.store'), [
                'jenis_usulan' => 'ssh',
                'tipe_perubahan' => 'baru',
                'alasan_usulan' => 'Tanpa harga.',
                'uraian' => 'Barang Tanpa Harga',
                'satuan' => 'Unit',
            ])
            ->assertSessionHasErrors('harga');

        $this->assertSame(0, Proposal::count());
    }

    public function test_menolak_usulan_sbu_tanpa_besaran_dan_kategori(): void
    {
        $this->actingAs($this->operator)
            ->post(route('opd.usulan.store'), [
                'jenis_usulan' => 'sbu',
                'tipe_perubahan' => 'baru',
                'alasan_usulan' => 'Tanpa besaran.',
                'uraian' => 'SBU Tidak Lengkap',
                'satuan' => 'OJ',
            ])
            ->assertSessionHasErrors(['besaran', 'kategori']);

        $this->assertSame(0, Proposal::count());
    }

    public function test_menolak_usulan_asb_tanpa_nama_kegiatan(): void
    {
        $this->actingAs($this->operator)
            ->post(route('opd.usulan.store'), [
                'jenis_usulan' => 'asb',
                'tipe_perubahan' => 'baru',
                'alasan_usulan' => 'Tanpa nama kegiatan.',
            ])
            ->assertSessionHasErrors('nama_kegiatan');

        $this->assertSame(0, Proposal::count());
    }

    public function test_menolak_jenis_usulan_di_luar_daftar(): void
    {
        $this->actingAs($this->operator)
            ->post(route('opd.usulan.store'), [
                'jenis_usulan' => 'jenis-karangan',
                'tipe_perubahan' => 'baru',
                'alasan_usulan' => 'Jenis tidak dikenal.',
                'uraian' => 'Apa saja',
                'satuan' => 'Unit',
            ])
            ->assertSessionHasErrors('jenis_usulan');

        $this->assertSame(0, Proposal::count());
    }

    public function test_menolak_harga_negatif(): void
    {
        $this->actingAs($this->operator)
            ->post(route('opd.usulan.store'), [
                'jenis_usulan' => 'ssh',
                'tipe_perubahan' => 'baru',
                'alasan_usulan' => 'Harga negatif.',
                'uraian' => 'Barang Harga Minus',
                'satuan' => 'Unit',
                'harga' => -5000,
            ])
            ->assertSessionHasErrors('harga');

        $this->assertSame(0, Proposal::count());
    }

    public function test_usulan_perubahan_wajib_menunjuk_item_yang_ada(): void
    {
        $this->actingAs($this->operator)
            ->post(route('opd.usulan.store'), [
                'jenis_usulan' => 'ssh',
                'tipe_perubahan' => 'perubahan',
                'alasan_usulan' => 'Perubahan tanpa menunjuk item.',
                'uraian' => 'Barang Perubahan',
                'satuan' => 'Unit',
                'harga' => 15000,
            ])
            ->assertSessionHasErrors('existing_item_id');
    }

    /** Input pengguna harus tampil sebagai teks biasa, bukan tereksekusi sebagai skrip. */
    public function test_input_berisi_skrip_ditampilkan_sebagai_teks(): void
    {
        $payload = '<script>alert(1)</script>';

        $proposal = $this->ajukan([
            'jenis_usulan' => 'ssh',
            'tipe_perubahan' => 'baru',
            'alasan_usulan' => $payload,
            'uraian' => 'Barang Uji XSS',
            'satuan' => 'Unit',
            'harga' => 1000,
        ]);

        $this->actingAs($this->operator)
            ->get(route('opd.usulan.show', $proposal))
            ->assertOk()
            ->assertDontSee($payload, false)
            ->assertSee(e($payload), false);
    }

    /** OPD tidak boleh menyentuh master secara langsung — semua perubahan harus lewat usulan. */
    public function test_usulan_belum_disetujui_tidak_mengubah_master(): void
    {
        $jumlahAwal = SshItem::count();

        $this->ajukan([
            'jenis_usulan' => 'ssh',
            'tipe_perubahan' => 'baru',
            'alasan_usulan' => 'Belum diverifikasi.',
            'uraian' => 'Barang Belum Disetujui',
            'satuan' => 'Unit',
            'harga' => 25000,
        ]);

        $this->assertSame($jumlahAwal, SshItem::count());
        $this->assertNull(SshItem::where('uraian', 'Barang Belum Disetujui')->first());
    }

    public function test_nomor_usulan_dibuat_otomatis_dan_unik(): void
    {
        $pertama = $this->ajukan([
            'jenis_usulan' => 'ssh', 'tipe_perubahan' => 'baru', 'alasan_usulan' => 'Usulan pertama.',
            'uraian' => 'Barang A', 'satuan' => 'Unit', 'harga' => 1000,
        ]);
        $kedua = $this->ajukan([
            'jenis_usulan' => 'ssh', 'tipe_perubahan' => 'baru', 'alasan_usulan' => 'Usulan kedua.',
            'uraian' => 'Barang B', 'satuan' => 'Unit', 'harga' => 2000,
        ]);

        $this->assertNotEmpty($pertama->nomor_usulan);
        $this->assertNotSame($pertama->nomor_usulan, $kedua->nomor_usulan);
    }

    public function test_usulan_tercatat_atas_nama_opd_pengusul(): void
    {
        $proposal = $this->ajukan([
            'jenis_usulan' => 'ssh', 'tipe_perubahan' => 'baru', 'alasan_usulan' => 'Cek OPD pengusul.',
            'uraian' => 'Barang OPD', 'satuan' => 'Unit', 'harga' => 3000,
        ]);

        $this->assertSame($this->operator->opd_id, $proposal->opd_id);
        $this->assertSame($this->operator->id, $proposal->created_by);
        $this->assertSame(TahunAnggaran::aktif()->id, $proposal->items->first()->data_usulan['tahun_anggaran_id']);
    }
}
