<?php

namespace Tests\Feature;

use App\Models\Disposisi;
use App\Models\SuratMasuk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PersuratanTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function dataSurat(array $ubah = []): array
    {
        return array_merge([
            'nomor_surat' => '005/123/DPU/2026',
            'tanggal_surat' => '2026-11-10',
            'tanggal_terima' => '2026-11-12',
            'asal_surat' => 'Dinas Pekerjaan Umum',
            'perihal' => 'Permohonan data hibah perumahan',
            'sifat' => 'biasa',
            'klasifikasi' => '640',
            'berkas' => [UploadedFile::fake()->image('foto-surat.jpg')],
        ], $ubah);
    }

    public function test_tamu_diarahkan_ke_halaman_masuk(): void
    {
        $this->get('/surat-masuk')->assertRedirect('/masuk');
    }

    public function test_operator_mencatat_surat_masuk_dan_berkas_tersimpan_terstruktur(): void
    {
        Storage::fake('arsip');
        $operator = $this->user('operator');

        $this->actingAs($operator)->post('/surat-masuk', $this->dataSurat())->assertRedirect();

        $surat = SuratMasuk::firstOrFail();
        $this->assertSame('0001/SM/2026', $surat->nomor_agenda);
        $dok = $surat->dokumen()->firstOrFail();
        $this->assertSame('masuk/2026/11/0001_dinas-pekerjaan-umum_permohonan-data-hibah-perumahan.jpg', $dok->path);
        Storage::disk('arsip')->assertExists($dok->path);
        $this->assertDatabaseHas('audit_logs', ['aksi' => 'buat', 'objek' => 'SuratMasuk']);
    }

    public function test_nomor_agenda_berurutan(): void
    {
        Storage::fake('arsip');
        $operator = $this->user('operator');
        $this->actingAs($operator)->post('/surat-masuk', $this->dataSurat());
        $this->actingAs($operator)->post('/surat-masuk', $this->dataSurat(['berkas' => [UploadedFile::fake()->image('b.png', 20, 20)]]));

        $this->assertSame(['0001/SM/2026', '0002/SM/2026'], SuratMasuk::orderBy('id')->pluck('nomor_agenda')->all());
    }

    public function test_berkas_duplikat_diperingatkan(): void
    {
        Storage::fake('arsip');
        $operator = $this->user('operator');
        $file = fn () => UploadedFile::fake()->createWithContent('a.pdf', '%PDF-1.4 sama');
        $this->actingAs($operator)->post('/surat-masuk', $this->dataSurat(['berkas' => [$file()]]));
        $this->actingAs($operator)->post('/surat-masuk', $this->dataSurat(['berkas' => [$file()]]))
            ->assertSessionHas('duplikat');

        $this->assertSame(1, SuratMasuk::count());
    }

    public function test_format_berkas_tidak_valid_ditolak(): void
    {
        Storage::fake('arsip');
        $this->actingAs($this->user('operator'))
            ->post('/surat-masuk', $this->dataSurat(['berkas' => [UploadedFile::fake()->create('virus.exe', 10)]]))
            ->assertSessionHasErrors('berkas.0');
    }

    public function test_staf_tidak_boleh_mencatat_surat(): void
    {
        $this->actingAs($this->user('staf'))->post('/surat-masuk', $this->dataSurat())->assertForbidden();
        $this->actingAs($this->user('staf'))->get('/surat-keluar')->assertForbidden();
    }

    public function test_pencarian_menemukan_berdasarkan_perihal(): void
    {
        Storage::fake('arsip');
        $operator = $this->user('operator');
        $this->actingAs($operator)->post('/surat-masuk', $this->dataSurat());

        $this->actingAs($operator)->get('/cari?q=HIBAH')->assertSee('0001/SM/2026');
        $this->actingAs($operator)->get('/cari?q=tidak-ada-kata-ini')->assertDontSee('0001/SM/2026');
    }

    public function test_pimpinan_membuat_disposisi_dan_staf_menindaklanjuti(): void
    {
        Storage::fake('arsip');
        $operator = $this->user('operator');
        $pimpinan = $this->user('pimpinan');
        $staf = $this->user('staf');
        $this->actingAs($operator)->post('/surat-masuk', $this->dataSurat());
        $surat = SuratMasuk::firstOrFail();

        // operator tidak boleh mendisposisi
        $this->actingAs($operator)->post("/surat-masuk/{$surat->id}/disposisi", [])->assertForbidden();
        // staf belum bisa melihat surat yang belum didisposisikan kepadanya
        $this->actingAs($staf)->get("/surat-masuk/{$surat->id}")->assertForbidden();

        $this->actingAs($pimpinan)->post("/surat-masuk/{$surat->id}/disposisi", [
            'kepada_user_id' => $staf->id,
            'instruksi' => ['Tindak lanjuti'],
            'batas_waktu' => now()->addDays(3)->toDateString(),
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame('didisposisi', $surat->fresh()->status);
        $this->actingAs($staf)->get("/surat-masuk/{$surat->id}")->assertOk();

        $disposisi = Disposisi::firstOrFail();
        $this->actingAs($staf)->patch("/disposisi/{$disposisi->id}", ['status' => 'selesai', 'tanggapan' => 'Sudah dibalas'])->assertRedirect();
        $this->assertSame('selesai', $surat->fresh()->status);
    }

    public function test_unduh_berkas_butuh_hak_akses(): void
    {
        Storage::fake('arsip');
        $operator = $this->user('operator');
        $this->actingAs($operator)->post('/surat-masuk', $this->dataSurat());
        $dok = SuratMasuk::firstOrFail()->dokumen()->firstOrFail();

        $this->actingAs($operator)->get("/dokumen/{$dok->id}")->assertOk();
        $this->actingAs($this->user('staf'))->get("/dokumen/{$dok->id}")->assertForbidden();
        auth()->logout();
        $this->get("/dokumen/{$dok->id}")->assertRedirect('/masuk');
    }

    public function test_surat_keluar_dicatat_dengan_nomor_sk(): void
    {
        Storage::fake('arsip');
        $this->actingAs($this->user('operator'))->post('/surat-keluar', [
            'nomor_surat' => '800/45/SETDA/2026', 'tanggal_surat' => '2026-11-13', 'tujuan' => 'Bupati',
            'perihal' => 'Laporan bulanan', 'sifat' => 'biasa',
            'berkas' => [UploadedFile::fake()->create('laporan.pdf', 100, 'application/pdf')],
        ])->assertRedirect();

        $this->assertDatabaseHas('surat_keluar', ['nomor_agenda' => '0001/SK/2026']);
        Storage::disk('arsip')->assertExists('keluar/2026/11/0001_bupati_laporan-bulanan.pdf');
    }

    public function test_semua_halaman_utama_dapat_dibuka(): void
    {
        Storage::fake('arsip');
        $operator = $this->user('operator');
        $this->actingAs($operator)->post('/surat-masuk', $this->dataSurat());
        $this->post('/surat-keluar', ['nomor_surat' => 'X/1', 'tanggal_surat' => '2026-11-13', 'tujuan' => 'Bupati', 'perihal' => 'Laporan', 'sifat' => 'biasa']);

        foreach (['/', '/surat-masuk', '/surat-masuk/baru', '/surat-keluar', '/surat-keluar/baru', '/surat-keluar/1', '/cari', '/surat-masuk?q=hibah&status=baru&sifat=biasa&tahun=2026'] as $url) {
            $this->actingAs($operator)->get($url)->assertOk();
        }
        $this->actingAs($this->user('pimpinan'))->get('/surat-masuk/1')->assertOk()->assertSee('Buat disposisi');
        $this->actingAs($this->user('staf'))->get('/')->assertOk();
        auth()->logout();
        $this->get('/masuk')->assertOk();
    }
}
