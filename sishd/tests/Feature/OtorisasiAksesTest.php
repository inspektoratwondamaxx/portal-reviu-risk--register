<?php

namespace Tests\Feature;

use App\Models\Opd;
use App\Models\Proposal;
use App\Models\TahunAnggaran;
use App\Models\User;
use App\Services\ProposalWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Autentikasi & otorisasi. Untuk sistem pemerintahan, kegagalan paling mahal bukan di alur normal
 * melainkan di batas akses: pengguna satu OPD melihat data OPD lain, atau role tanpa kewenangan
 * memanggil endpoint langsung lewat URL tanpa melalui menu.
 */
class OtorisasiAksesTest extends TestCase
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

    private function buatUsulanUntukOpd(int $opdId, User $pengusul, string $uraian = 'Barang Uji'): Proposal
    {
        return app(ProposalWorkflowService::class)->createProposal(
            opdId: $opdId,
            jenisUsulan: 'ssh',
            tipePerubahan: 'baru',
            alasan: 'Pengujian otorisasi.',
            items: [['data_usulan' => [
                'uraian' => $uraian,
                'satuan' => 'Unit',
                'harga' => 10000,
                'tahun_anggaran_id' => TahunAnggaran::aktif()->id,
            ]]],
            creator: $pengusul,
        );
    }

    // --- Autentikasi ---

    public function test_login_dengan_kredensial_benar(): void
    {
        $this->post(route('login'), ['email' => 'operator@sishd.test', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($this->user('operator@sishd.test'));
    }

    public function test_login_gagal_dengan_kata_sandi_salah(): void
    {
        $this->post(route('login'), ['email' => 'operator@sishd.test', 'password' => 'salah-total'])
            ->assertSessionHasErrors();

        $this->assertGuest();
    }

    public function test_halaman_terproteksi_menolak_pengguna_belum_login(): void
    {
        $this->assertGuest();

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('opd.usulan.index'))->assertRedirect(route('login'));
        $this->get('/admin/ssh')->assertRedirect(route('login'));
    }

    public function test_situs_publik_tetap_bisa_diakses_tanpa_login(): void
    {
        $this->assertGuest();

        $this->get('/')->assertOk();
    }

    // --- Otorisasi antar-OPD (IDOR) ---

    public function test_opd_tidak_bisa_membuka_usulan_milik_opd_lain(): void
    {
        $operatorPu = $this->user('operator@sishd.test');
        $opdLain = Opd::where('id', '!=', $operatorPu->opd_id)->firstOrFail();

        // Usulan milik OPD lain, diajukan oleh pengguna lain.
        $usulanOpdLain = $this->buatUsulanUntukOpd($opdLain->id, $this->user('inspektoratwondamaxx@gmail.com'), 'Barang OPD Lain');

        $this->actingAs($operatorPu)
            ->get(route('opd.usulan.show', $usulanOpdLain))
            ->assertForbidden();
    }

    public function test_daftar_usulan_opd_hanya_menampilkan_milik_sendiri(): void
    {
        $operatorPu = $this->user('operator@sishd.test');
        $opdLain = Opd::where('id', '!=', $operatorPu->opd_id)->firstOrFail();

        $milikSendiri = $this->buatUsulanUntukOpd($operatorPu->opd_id, $operatorPu, 'Barang Milik Sendiri');
        $milikOpdLain = $this->buatUsulanUntukOpd($opdLain->id, $this->user('inspektoratwondamaxx@gmail.com'), 'Barang OPD Lain');

        $this->actingAs($operatorPu)
            ->get(route('opd.usulan.index'))
            ->assertOk()
            ->assertSee($milikSendiri->nomor_usulan)
            ->assertDontSee($milikOpdLain->nomor_usulan);
    }

    public function test_opd_tidak_bisa_mengajukan_ulang_usulan_opd_lain(): void
    {
        $operatorPu = $this->user('operator@sishd.test');
        $opdLain = Opd::where('id', '!=', $operatorPu->opd_id)->firstOrFail();
        $usulanOpdLain = $this->buatUsulanUntukOpd($opdLain->id, $this->user('inspektoratwondamaxx@gmail.com'));

        $this->actingAs($operatorPu)
            ->post(route('opd.usulan.ajukan-ulang', $usulanOpdLain))
            ->assertForbidden();
    }

    /**
     * Verifikator memakai layar verifikasi tersendiri, bukan layar OPD — jadi pintu masuk lewat
     * /usulan tetap tertutup untuknya (hak akses seminimal yang diperlukan), sementara data yang
     * sama tetap bisa ia periksa lewat /verifikasi.
     */
    public function test_verifikator_memakai_layar_verifikasi_bukan_layar_opd(): void
    {
        $operatorPu = $this->user('operator@sishd.test');
        $usulan = $this->buatUsulanUntukOpd($operatorPu->opd_id, $operatorPu);
        $verifikator = $this->user('verifikator@sishd.test');

        $this->actingAs($verifikator)->get(route('opd.usulan.show', $usulan))->assertForbidden();
        $this->actingAs($verifikator)->get(route('verifikasi.show', $usulan))->assertOk();
    }

    public function test_super_admin_boleh_melihat_usulan_lintas_opd(): void
    {
        $operatorPu = $this->user('operator@sishd.test');
        $usulan = $this->buatUsulanUntukOpd($operatorPu->opd_id, $operatorPu);

        $this->actingAs($this->user('inspektoratwondamaxx@gmail.com'))
            ->get(route('opd.usulan.show', $usulan))
            ->assertOk();
    }

    // --- Batas kewenangan per role ---

    public function test_operator_opd_tidak_bisa_mengubah_master_data_langsung(): void
    {
        $operator = $this->user('operator@sishd.test');

        $this->actingAs($operator)->get('/admin/ssh')->assertForbidden();
        $this->actingAs($operator)->get(route('verifikasi.index'))->assertForbidden();
    }

    public function test_operator_opd_tidak_bisa_memutuskan_verifikasi_lewat_url_langsung(): void
    {
        $operator = $this->user('operator@sishd.test');
        $usulan = $this->buatUsulanUntukOpd($operator->opd_id, $operator);

        $this->actingAs($operator)
            ->post(route('verifikasi.putuskan', $usulan), ['keputusan' => 'setuju'])
            ->assertForbidden();

        $this->assertSame('menunggu_verifikasi', $usulan->fresh()->status->value);
    }

    public function test_verifikator_tidak_bisa_membuka_menu_sistem(): void
    {
        $this->actingAs($this->user('verifikator@sishd.test'))
            ->get('/sistem/opd')
            ->assertForbidden();
    }

    public function test_super_admin_bisa_membuka_seluruh_menu(): void
    {
        $superAdmin = $this->user('inspektoratwondamaxx@gmail.com');

        $this->actingAs($superAdmin)->get('/admin/ssh')->assertOk();
        $this->actingAs($superAdmin)->get(route('verifikasi.index'))->assertOk();
        $this->actingAs($superAdmin)->get('/sistem/opd')->assertOk();
    }

    public function test_pengguna_keluar_kehilangan_akses(): void
    {
        $this->actingAs($this->user('operator@sishd.test'))
            ->post(route('logout'))
            ->assertRedirect();

        $this->assertGuest();
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }
}
