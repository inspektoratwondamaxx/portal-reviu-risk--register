<?php

namespace Tests\Feature;

use App\Models\PriceEvidence;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Pemagar temuan review keamanan (Modul N). Berbeda dari modul lain, tes di sini tidak menguji
 * "fitur berjalan" melainkan "serangan tertentu gagal" — sehingga bila kelak ada perubahan kode
 * yang tanpa sadar membuka kembali celahnya, CI langsung menolaknya.
 */
class KeamananTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\OpdSeeder::class);
        $this->seed(\Database\Seeders\TahunAnggaranSeeder::class);
        $this->seed(\Database\Seeders\UserSeeder::class);

        RateLimiter::clear('');
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function kirimBukti(UploadedFile $berkas)
    {
        return $this->actingAs($this->user('operator@sishd.test'))->post(route('survei-harga.store'), [
            'uraian_barang' => 'Uji Unggah Bukti',
            'tanggal_survei' => '2026-03-01',
            'harga' => 1000,
            'bukti' => [$berkas],
        ]);
    }

    // --- T-11: SVG bermuatan skrip (stored XSS) ---

    public function test_bukti_survei_berformat_svg_ditolak(): void
    {
        Storage::fake('public');

        // SVG adalah dokumen XML yang boleh memuat <script>. Berkas bukti dibuka lewat tautan ke
        // domain aplikasi sendiri, jadi skrip di dalamnya akan berjalan dengan sesi pembukanya.
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';

        $this->kirimBukti(UploadedFile::fake()->createWithContent('bukti.svg', $svg))
            ->assertSessionHasErrors('bukti.0');

        $this->assertSame(0, PriceEvidence::count());
    }

    public function test_bukti_survei_berkas_html_dan_php_ditolak(): void
    {
        Storage::fake('public');

        foreach (['jahat.html' => '<script>alert(1)</script>', 'jahat.php' => '<?php echo 1;'] as $nama => $isi) {
            $this->kirimBukti(UploadedFile::fake()->createWithContent($nama, $isi))
                ->assertSessionHasErrors('bukti.0');
        }

        $this->assertSame(0, PriceEvidence::count());
    }

    public function test_bukti_survei_berformat_foto_biasa_tetap_diterima(): void
    {
        Storage::fake('public');

        // Perbaikannya tidak boleh sampai menutup jalur yang memang dibutuhkan petugas lapangan.
        $this->kirimBukti(UploadedFile::fake()->image('foto-toko.jpg'))
            ->assertRedirect(route('survei-harga.index'));

        $this->assertSame(1, PriceEvidence::count());
        Storage::disk('public')->assertExists(PriceEvidence::firstOrFail()->file_path);
    }

    // --- T-12: percobaan kata sandi tanpa batas ---

    public function test_percobaan_login_gagal_berulang_diblokir(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.attempt'), [
                'email' => 'inspektoratwondamaxx@gmail.com',
                'password' => "tebakan-$i",
            ])->assertStatus(302);
        }

        $this->post(route('login.attempt'), [
            'email' => 'inspektoratwondamaxx@gmail.com',
            'password' => 'tebakan-berikutnya',
        ])->assertStatus(429);

        $this->assertGuest();
    }

    // --- T-13: Super Admin terakhir menghilangkan dirinya sendiri ---

    public function test_super_admin_terakhir_tidak_bisa_menurunkan_role_sendiri(): void
    {
        $superAdmin = $this->user('inspektoratwondamaxx@gmail.com');
        $pimpinan = Role::where('name', 'pimpinan')->firstOrFail();

        $this->actingAs($superAdmin)->put(route('sistem.users.update', $superAdmin), [
            'name' => $superAdmin->name,
            'email' => $superAdmin->email,
            'role_id' => $pimpinan->id,
            'is_active' => 1,
        ])->assertSessionHasErrors('user');

        $this->assertTrue($superAdmin->fresh()->isSuperAdmin());
    }

    public function test_super_admin_terakhir_tidak_bisa_menonaktifkan_akun_sendiri(): void
    {
        $superAdmin = $this->user('inspektoratwondamaxx@gmail.com');

        $this->actingAs($superAdmin)->put(route('sistem.users.update', $superAdmin), [
            'name' => $superAdmin->name,
            'email' => $superAdmin->email,
            'role_id' => $superAdmin->role_id,
            'is_active' => 0,
        ])->assertSessionHasErrors('user');

        $this->assertTrue($superAdmin->fresh()->is_active);
    }

    public function test_super_admin_boleh_turun_role_bila_masih_ada_super_admin_lain(): void
    {
        $superAdmin = $this->user('inspektoratwondamaxx@gmail.com');
        $pimpinan = Role::where('name', 'pimpinan')->firstOrFail();

        User::create([
            'name' => 'Super Admin Cadangan',
            'email' => 'cadangan@sishd.test',
            'password' => 'rahasia-panjang',
            'role_id' => $superAdmin->role_id,
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)->put(route('sistem.users.update', $superAdmin), [
            'name' => $superAdmin->name,
            'email' => $superAdmin->email,
            'role_id' => $pimpinan->id,
            'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertFalse($superAdmin->fresh()->isSuperAdmin());
    }

    public function test_super_admin_terakhir_tidak_bisa_dihapus(): void
    {
        $superAdmin = $this->user('inspektoratwondamaxx@gmail.com');
        $adminSsh = $this->user('adminssh@sishd.test');
        $adminSsh->forceFill(['role_id' => $superAdmin->role_id])->save();

        // Super Admin cadangan menghapus Super Admin utama — masih boleh, sisa satu.
        $this->actingAs($adminSsh)->delete(route('sistem.users.destroy', $superAdmin))
            ->assertSessionHasNoErrors();

        // Kini hanya tersisa dirinya sendiri; penghapusan berikutnya harus ditolak.
        $operator = $this->user('operator@sishd.test');
        $this->actingAs($adminSsh)->delete(route('sistem.users.destroy', $operator))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, User::whereHas('role', fn ($q) => $q->where('name', 'super_admin'))->count());
    }

    // --- T-14: header keamanan ---

    public function test_respons_memuat_header_keamanan_dasar(): void
    {
        $respons = $this->get(route('publik.beranda'))->assertOk();

        // Tanpa X-Frame-Options, halaman persetujuan bisa dimuat di iframe tersembunyi situs lain
        // sehingga klik pejabat berwenang diarahkan ke tombol "Setujui" tanpa disadari.
        $this->assertSame('SAMEORIGIN', $respons->headers->get('X-Frame-Options'));
        $this->assertSame('nosniff', $respons->headers->get('X-Content-Type-Options'));
        $this->assertSame('same-origin', $respons->headers->get('Referrer-Policy'));
    }

    public function test_header_keamanan_ikut_pada_halaman_back_office(): void
    {
        $this->actingAs($this->user('adminssh@sishd.test'))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    // --- Pemagar hal yang sudah benar, supaya tidak diturunkan tanpa sengaja ---

    public function test_kata_sandi_tersimpan_dalam_bentuk_hash(): void
    {
        $this->actingAs($this->user('inspektoratwondamaxx@gmail.com'))->post(route('sistem.users.store'), [
            'name' => 'Pengguna Uji',
            'email' => 'uji-hash@sishd.test',
            'role_id' => Role::where('name', 'opd_operator')->firstOrFail()->id,
            'password' => 'kata-sandi-uji-123',
        ])->assertRedirect();

        $baru = User::where('email', 'uji-hash@sishd.test')->firstOrFail();

        $this->assertNotSame('kata-sandi-uji-123', $baru->password);
        $this->assertTrue(password_verify('kata-sandi-uji-123', $baru->password));
    }

    public function test_api_publik_tidak_membocorkan_kolom_internal(): void
    {
        $data = $this->getJson(route('api.v1.ssh.index'))->assertOk()->json('data');

        foreach ($data as $baris) {
            foreach (['created_by', 'updated_by', 'id', 'opd_id', 'is_active'] as $kolomInternal) {
                $this->assertArrayNotHasKey($kolomInternal, $baris);
            }
        }
    }

    public function test_pesan_login_gagal_tidak_membocorkan_email_terdaftar(): void
    {
        // Pesan untuk email terdaftar dan tidak terdaftar harus sama, supaya penyerang tidak bisa
        // memakai halaman login untuk memetakan daftar email pegawai yang punya akun.
        $terdaftar = $this->post(route('login.attempt'), ['email' => 'operator@sishd.test', 'password' => 'salah']);
        $tidakAda = $this->post(route('login.attempt'), ['email' => 'bukan-siapa-siapa@sishd.test', 'password' => 'salah']);

        $this->assertSame(
            $terdaftar->getSession()->get('errors')->first('email'),
            $tidakAda->getSession()->get('errors')->first('email'),
        );
    }
}
