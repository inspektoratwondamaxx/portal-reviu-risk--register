<?php

namespace Database\Seeders;

use App\Models\Asb;
use App\Models\AsbFormula;
use App\Models\AsbVariable;
use App\Models\Hspk;
use App\Models\HspkComponent;
use App\Models\Opd;
use App\Models\PriceSurvey;
use App\Models\Proposal;
use App\Models\ProposalItem;
use App\Models\SbuItem;
use App\Models\SshItem;
use App\Models\TahunAnggaran;
use App\Models\User;
use App\Models\Vendor;
use App\Services\AsbCalculationService;
use App\Services\HspkCalculationService;
use Illuminate\Database\Seeder;

/**
 * Data contoh mengikuti persis angka pada desain awal (dashboard & Daftar SSH) supaya demo terasa
 * konsisten dengan mockup: Semen Portland 40kg, Besi Beton, Pasir Pasang, Upah Pekerja, HSPK Beton
 * K-250. Kenaikan harga di akhir seeder sengaja dijalankan lewat save() (bukan insert langsung)
 * agar price_histories & kaskade HSPK/ASB benar-benar teruji jalan otomatis, bukan cuma didesain.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $tahun = TahunAnggaran::where('tahun', 2026)->first();
        $superAdmin = User::where('email', 'inspektoratwondamaxx@gmail.com')->first();

        $ssh = fn (array $attrs) => SshItem::create(array_merge([
            'tahun_anggaran_id' => $tahun->id, 'status' => 'aktif', 'is_active' => true, 'created_by' => $superAdmin->id,
        ], $attrs));

        $semenTigaRoda = $ssh(['kode_barang' => '1.01.0001', 'uraian' => 'Semen Portland 40 Kg', 'spesifikasi' => 'PC Type I (SNI)', 'merek' => 'Tiga Roda', 'satuan' => 'Zak', 'harga' => 78000, 'sumber_harga' => 'Survei harga']);
        $ssh(['kode_barang' => '1.01.0002', 'uraian' => 'Semen Portland 40 Kg', 'spesifikasi' => 'PC Type I (SNI)', 'merek' => 'Gresik', 'satuan' => 'Zak', 'harga' => 80500, 'sumber_harga' => 'Survei harga']);
        $ssh(['kode_barang' => '1.01.0003', 'uraian' => 'Semen Portland 50 Kg', 'spesifikasi' => 'Polos', 'merek' => 'Gresik', 'satuan' => 'Zak', 'harga' => 98000, 'sumber_harga' => 'Survei harga']);
        $besi10 = $ssh(['kode_barang' => '1.01.0004', 'uraian' => 'Besi Beton Ø10 mm', 'spesifikasi' => 'Polos SNI', 'satuan' => 'Batang', 'harga' => 15500, 'sumber_harga' => 'Survei harga']);
        $ssh(['kode_barang' => '1.01.0005', 'uraian' => 'Besi Beton Ø12 mm', 'spesifikasi' => 'Polos SNI', 'satuan' => 'Batang', 'harga' => 22500, 'sumber_harga' => 'Survei harga']);
        $pasir = $ssh(['kode_barang' => '1.02.0001', 'uraian' => 'Pasir Pasang', 'satuan' => 'M3', 'harga' => 220000, 'sumber_harga' => 'Survei harga']);
        $batuPecah = $ssh(['kode_barang' => '1.02.0002', 'uraian' => 'Batu Pecah 2/3', 'satuan' => 'M3', 'harga' => 260000, 'sumber_harga' => 'Survei harga']);
        $air = $ssh(['kode_barang' => '1.02.0003', 'uraian' => 'Air Kerja', 'satuan' => 'Liter', 'harga' => 50, 'sumber_harga' => 'Estimasi']);

        $sbu = fn (array $attrs) => SbuItem::create(array_merge([
            'tahun_anggaran_id' => $tahun->id, 'status' => 'aktif', 'is_active' => true, 'created_by' => $superAdmin->id,
        ], $attrs));

        $upahPekerja = $sbu(['kode' => '2.01.0001', 'kategori' => 'lainnya', 'uraian' => 'Upah Pekerja Konstruksi', 'satuan' => 'OH', 'besaran' => 120000, 'dasar_penetapan' => 'SK Bupati Upah Minimum']);
        $mixer = $sbu(['kode' => '2.01.0002', 'kategori' => 'lainnya', 'uraian' => 'Sewa Mixer Molen', 'satuan' => 'Jam', 'besaran' => 25000, 'dasar_penetapan' => 'Survei harga']);
        $sbu(['kode' => '3.01.0001', 'kategori' => 'honorarium', 'uraian' => 'Honorarium Narasumber', 'satuan' => 'OJ', 'besaran' => 900000, 'dasar_penetapan' => 'SK Bupati']);
        $sbu(['kode' => '4.01.0001', 'kategori' => 'perjalanan_dinas', 'uraian' => 'Perjalanan Dinas Dalam Daerah', 'satuan' => 'OH', 'besaran' => 250000, 'dasar_penetapan' => 'SK Bupati']);

        // --- HSPK Pekerjaan Beton K-250 (Bab 8 kajian) ---
        $hspkBeton = Hspk::create([
            'kode' => 'HSPK-001', 'uraian' => 'Pekerjaan Beton K-250', 'jenis_pekerjaan' => 'Struktur Beton',
            'satuan' => 'M3', 'tahun_anggaran_id' => $tahun->id, 'status' => 'aktif', 'is_active' => true,
            'created_by' => $superAdmin->id,
        ]);

        foreach ([
            ['komponen_type' => 'material', 'ssh_item_id' => $semenTigaRoda->id, 'koefisien' => 8.50, 'satuan' => 'Zak', 'urutan' => 1],
            ['komponen_type' => 'material', 'ssh_item_id' => $pasir->id, 'koefisien' => 0.60, 'satuan' => 'M3', 'urutan' => 2],
            ['komponen_type' => 'material', 'ssh_item_id' => $batuPecah->id, 'koefisien' => 0.80, 'satuan' => 'M3', 'urutan' => 3],
            ['komponen_type' => 'material', 'ssh_item_id' => $air->id, 'koefisien' => 215, 'satuan' => 'Liter', 'urutan' => 4],
            ['komponen_type' => 'tenaga_kerja', 'sbu_item_id' => $upahPekerja->id, 'koefisien' => 1.65, 'satuan' => 'OH', 'urutan' => 5],
            ['komponen_type' => 'peralatan', 'sbu_item_id' => $mixer->id, 'koefisien' => 0.75, 'satuan' => 'Jam', 'urutan' => 6],
        ] as $component) {
            HspkComponent::create(array_merge($component, ['hspk_id' => $hspkBeton->id]));
        }

        app(HspkCalculationService::class)->recalculate($hspkBeton, 'Perhitungan awal HSPK');

        // --- ASB Pembangunan Gedung Pemerintah (Bab 9 kajian) ---
        $asbGedung = Asb::create([
            'kode' => 'ASB-001', 'nama_kegiatan' => 'Pembangunan Gedung Pemerintah', 'kelompok_kegiatan' => 'Belanja Modal Gedung',
            'satuan_variabel' => 'M2', 'batas_minimal' => 3000000000, 'batas_maksimal' => 4500000000,
            'tahun_anggaran_id' => $tahun->id, 'status' => 'aktif', 'is_active' => true, 'created_by' => $superAdmin->id,
        ]);
        AsbVariable::create(['asb_id' => $asbGedung->id, 'kode_variabel' => 'luas_bangunan', 'label' => 'Luas Bangunan', 'nilai' => 500, 'satuan' => 'M2', 'sumber_tipe' => 'manual', 'urutan' => 1]);
        AsbVariable::create(['asb_id' => $asbGedung->id, 'kode_variabel' => 'standar_biaya_per_m2', 'label' => 'Standar Biaya per M2', 'nilai' => 7500000, 'satuan' => 'Rp/M2', 'sumber_tipe' => 'manual', 'urutan' => 2]);
        AsbFormula::create(['asb_id' => $asbGedung->id, 'ekspresi' => '{luas_bangunan} * {standar_biaya_per_m2}', 'keterangan' => 'Estimasi biaya = luas bangunan x standar biaya per m2', 'created_by' => $superAdmin->id]);
        app(AsbCalculationService::class)->recalculate($asbGedung);

        // --- Survei harga: dasar kenaikan harga Semen Portland 40 Kg (Bab 15 kajian) ---
        $vendors = Vendor::orderBy('id')->take(3)->get();
        foreach ([[80000, '2026-08-01'], [82000, '2026-08-02'], [79000, '2026-08-03']] as $i => [$harga, $tanggal]) {
            PriceSurvey::create([
                'ssh_item_id' => $semenTigaRoda->id, 'uraian_barang' => 'Semen Portland 40 Kg', 'merek' => 'Tiga Roda',
                'vendor_id' => $vendors[$i]->id ?? null, 'lokasi' => 'Kota Gresik', 'tanggal_survei' => $tanggal,
                'harga' => $harga, 'surveyor_id' => $superAdmin->id,
            ]);
        }

        // --- Kenaikan harga (memicu price_histories + kaskade HSPK otomatis lewat save(), bukan insert manual) ---
        $semenTigaRoda->pendingDasarPerubahan = 'Survei harga Agustus 2026';
        $semenTigaRoda->update(['harga' => 82000]);

        $besi10->pendingDasarPerubahan = 'Survei harga Agustus 2026';
        $besi10->update(['harga' => 16200]);

        $pasir->pendingDasarPerubahan = 'Survei harga Agustus 2026';
        $pasir->update(['harga' => 235000]);

        $upahPekerja->pendingDasarPerubahan = 'Penyesuaian SK Bupati Upah Minimum 2026';
        $upahPekerja->update(['besaran' => 130000]);

        // --- Contoh Usulan OPD (Bab 11 kajian) ---
        $dinasPu = Opd::where('kode', '1.02')->first();
        $operator = User::where('email', 'operator@sishd.test')->first();
        $verifikator = User::where('email', 'verifikator@sishd.test')->first();

        $menunggu = Proposal::create([
            'nomor_usulan' => Proposal::generateNomor('ssh'), 'opd_id' => $dinasPu->id, 'jenis_usulan' => 'ssh',
            'tipe_perubahan' => 'baru', 'status' => 'menunggu_verifikasi',
            'alasan_usulan' => 'Item belum tersedia di master data untuk kebutuhan RAB rehab drainase.',
            'diajukan_at' => now()->subDays(2), 'created_by' => $operator?->id,
        ]);
        ProposalItem::create([
            'proposal_id' => $menunggu->id, 'item_type' => 'ssh',
            'data_usulan' => [
                'uraian' => 'Paku Beton 5 cm', 'spesifikasi' => '-', 'merek' => 'Besi', 'satuan' => 'Kg',
                'harga' => 23000, 'sumber_harga' => 'Survei toko', 'keterangan' => 'Belum tersedia di master data',
                'tahun_anggaran_id' => $tahun->id,
            ],
        ]);

        $disetujui = Proposal::create([
            'nomor_usulan' => Proposal::generateNomor('ssh'), 'opd_id' => $dinasPu->id, 'jenis_usulan' => 'ssh',
            'tipe_perubahan' => 'baru', 'status' => 'disetujui', 'alasan_usulan' => 'Kebutuhan pengaspalan jalan.',
            'diajukan_at' => now()->subDays(10), 'verified_at' => now()->subDays(8),
            'verifikator_id' => $verifikator?->id, 'catatan_verifikasi' => 'Data sesuai hasil survei, disetujui.',
            'created_by' => $operator?->id,
        ]);
        ProposalItem::create([
            'proposal_id' => $disetujui->id, 'item_type' => 'ssh', 'created_item_id' => $besi10->id,
            'data_usulan' => ['uraian' => $besi10->uraian, 'harga' => (float) $besi10->harga],
        ]);

        $ditolak = Proposal::create([
            'nomor_usulan' => Proposal::generateNomor('ssh'), 'opd_id' => $dinasPu->id, 'jenis_usulan' => 'ssh',
            'tipe_perubahan' => 'baru', 'status' => 'ditolak', 'alasan_usulan' => 'Duplikat data lama.',
            'diajukan_at' => now()->subDays(15), 'verified_at' => now()->subDays(14),
            'verifikator_id' => $verifikator?->id,
            'catatan_verifikasi' => 'Item sudah ada dengan uraian & spesifikasi yang sama.', 'created_by' => $operator?->id,
        ]);
        ProposalItem::create([
            'proposal_id' => $ditolak->id, 'item_type' => 'ssh',
            'data_usulan' => ['uraian' => 'Semen Portland 40 Kg', 'merek' => 'Tiga Roda', 'harga' => 82000],
        ]);
    }
}
