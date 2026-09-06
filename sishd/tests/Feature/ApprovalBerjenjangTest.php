<?php

namespace Tests\Feature;

use App\Models\Opd;
use App\Models\Proposal;
use App\Models\SshItem;
use App\Models\TahunAnggaran;
use App\Models\User;
use App\Services\ProposalWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Approval berjenjang (Bab 11 & 22.3 kajian): Verifikator -> Tim Standar Harga -> Pejabat
 * Berwenang. Yang dikunci di sini adalah dua hal yang paling mudah rusak diam-diam saat kode
 * diubah: (1) data master TIDAK boleh muncul sebelum tahap terakhir menyetujui, dan (2) reviewer
 * tidak boleh memutuskan usulan yang bukan tahapnya.
 */
class ApprovalBerjenjangTest extends TestCase
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

    private function buatUsulanSsh(): Proposal
    {
        $operator = User::where('email', 'operator@sishd.test')->firstOrFail();

        return app(ProposalWorkflowService::class)->createProposal(
            opdId: Opd::where('kode', '1.02')->value('id'),
            jenisUsulan: 'ssh',
            tipePerubahan: 'baru',
            alasan: 'Kebutuhan pemeliharaan jalan.',
            items: [[
                'data_usulan' => [
                    'uraian' => 'Kabel NYM 3x2.5 Uji',
                    'satuan' => 'Meter',
                    'harga' => 12000,
                    'tahun_anggaran_id' => TahunAnggaran::aktif()->id,
                ],
            ]],
            creator: $operator,
        );
    }

    private function setujui(Proposal $proposal, string $email): Proposal
    {
        $reviewer = User::where('email', $email)->firstOrFail();

        return app(ProposalWorkflowService::class)->review($proposal, $reviewer, 'setuju', 'Disetujui.');
    }

    public function test_usulan_baru_mulai_dari_tahap_pertama(): void
    {
        $proposal = $this->buatUsulanSsh();

        $this->assertSame('verifikator', $proposal->tahapan_saat_ini);
        $this->assertSame('menunggu_verifikasi', $proposal->status->value);
        $this->assertSame(1, $proposal->tahapanKe());
    }

    public function test_data_master_baru_dibuat_setelah_tahap_terakhir(): void
    {
        $proposal = $this->buatUsulanSsh();
        $jumlahAwal = SshItem::count();

        // Tahap 1 -> maju ke tim standar harga, belum materialisasi.
        $proposal = $this->setujui($proposal, 'verifikator@sishd.test');
        $this->assertSame('tim_standar_harga', $proposal->tahapan_saat_ini);
        $this->assertSame('menunggu_verifikasi', $proposal->status->value);
        $this->assertSame($jumlahAwal, SshItem::count(), 'SSH tidak boleh bertambah di tahap 1.');

        // Tahap 2 -> maju ke pejabat berwenang, masih belum materialisasi.
        $proposal = $this->setujui($proposal, 'timstandarharga@sishd.test');
        $this->assertSame('pejabat_berwenang', $proposal->tahapan_saat_ini);
        $this->assertSame('menunggu_verifikasi', $proposal->status->value);
        $this->assertSame($jumlahAwal, SshItem::count(), 'SSH tidak boleh bertambah di tahap 2.');

        // Tahap 3 (terakhir) -> disetujui penuh & data master masuk.
        $proposal = $this->setujui($proposal, 'pejabat@sishd.test');
        $this->assertSame('disetujui', $proposal->status->value);
        $this->assertSame($jumlahAwal + 1, SshItem::count(), 'SSH harus bertambah setelah tahap akhir.');

        $item = SshItem::where('uraian', 'Kabel NYM 3x2.5 Uji')->first();
        $this->assertNotNull($item);
        $this->assertTrue($item->is_active);
        $this->assertEquals(12000, $item->harga);
    }

    public function test_setiap_tahap_tercatat_di_riwayat_verifikasi(): void
    {
        $proposal = $this->buatUsulanSsh();
        $proposal = $this->setujui($proposal, 'verifikator@sishd.test');
        $proposal = $this->setujui($proposal, 'timstandarharga@sishd.test');
        $proposal = $this->setujui($proposal, 'pejabat@sishd.test');

        $this->assertSame(
            ['verifikator', 'tim_standar_harga', 'pejabat_berwenang'],
            $proposal->reviews()->orderBy('id')->pluck('tahapan')->all(),
            'Tiap keputusan harus tercatat pada tahap tempat keputusan itu diambil.'
        );
    }

    public function test_reviewer_tahap_lain_tidak_bisa_memutuskan(): void
    {
        $proposal = $this->buatUsulanSsh(); // masih di tahap verifikator
        $pejabat = User::where('email', 'pejabat@sishd.test')->firstOrFail();

        $this->actingAs($pejabat)
            ->post(route('verifikasi.putuskan', $proposal), ['keputusan' => 'setuju', 'catatan' => 'lompat tahap'])
            ->assertForbidden();

        $this->assertSame('verifikator', $proposal->fresh()->tahapan_saat_ini);
        $this->assertSame(0, $proposal->reviews()->count());
    }

    public function test_form_keputusan_hanya_muncul_untuk_pemilik_tahap(): void
    {
        $proposal = $this->buatUsulanSsh();

        $this->actingAs(User::where('email', 'pejabat@sishd.test')->firstOrFail())
            ->get(route('verifikasi.show', $proposal))
            ->assertOk()
            ->assertSee('bukan tahap Anda', false)
            ->assertDontSee('name="keputusan"', false);

        $this->actingAs(User::where('email', 'verifikator@sishd.test')->firstOrFail())
            ->get(route('verifikasi.show', $proposal))
            ->assertOk()
            ->assertSee('name="keputusan"', false);
    }

    public function test_antrean_verifikasi_disaring_per_tahap(): void
    {
        $proposal = $this->buatUsulanSsh();

        $this->actingAs(User::where('email', 'verifikator@sishd.test')->firstOrFail())
            ->get(route('verifikasi.index'))
            ->assertOk()
            ->assertSee($proposal->nomor_usulan);

        $this->actingAs(User::where('email', 'pejabat@sishd.test')->firstOrFail())
            ->get(route('verifikasi.index'))
            ->assertOk()
            ->assertDontSee($proposal->nomor_usulan);
    }

    public function test_revisi_mengulang_dari_tahap_pertama(): void
    {
        $proposal = $this->buatUsulanSsh();
        $proposal = $this->setujui($proposal, 'verifikator@sishd.test');
        $this->assertSame('tim_standar_harga', $proposal->tahapan_saat_ini);

        $timStandarHarga = User::where('email', 'timstandarharga@sishd.test')->firstOrFail();
        $proposal = app(ProposalWorkflowService::class)->review($proposal, $timStandarHarga, 'revisi', 'Harga perlu dilengkapi survei.');
        $this->assertSame('revisi', $proposal->status->value);

        $proposal = app(ProposalWorkflowService::class)->resubmit($proposal);

        $this->assertSame('menunggu_verifikasi', $proposal->status->value);
        $this->assertSame('verifikator', $proposal->tahapan_saat_ini, 'Usulan yang direvisi harus diperiksa ulang dari tahap awal.');
    }

    public function test_penolakan_menghentikan_rantai_tanpa_membuat_data_master(): void
    {
        $proposal = $this->buatUsulanSsh();
        $jumlahAwal = SshItem::count();

        $verifikator = User::where('email', 'verifikator@sishd.test')->firstOrFail();
        $proposal = app(ProposalWorkflowService::class)->review($proposal, $verifikator, 'tolak', 'Tidak sesuai kebutuhan.');

        $this->assertSame('ditolak', $proposal->status->value);
        $this->assertSame($jumlahAwal, SshItem::count());
    }

    public function test_pimpinan_tidak_bisa_membuka_menu_teknis(): void
    {
        $pimpinan = User::where('email', 'pimpinan@sishd.test')->firstOrFail();

        $this->actingAs($pimpinan)->get(route('dashboard'))->assertOk();
        $this->actingAs($pimpinan)->get('/admin/ssh')->assertForbidden();
        $this->actingAs($pimpinan)->get(route('verifikasi.index'))->assertForbidden();
    }
}
