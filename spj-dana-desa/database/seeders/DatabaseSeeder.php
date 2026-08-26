<?php

namespace Database\Seeders;

use App\Models\BidangKegiatan;
use App\Models\Kampung;
use App\Models\Kegiatan;
use App\Models\KodeRekening;
use App\Models\PendampingWilayah;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Data contoh untuk pengembangan & UAT — BUKAN data 75 kampung riil Kabupaten Teluk Wondama.
 * Master data kampung sesungguhnya wajib diimpor Admin lewat modul KF-17 (POST
 * /api/v1/admin/kampung) sebelum rollout sesuai Bab VII.1 Tahap 5 kajian teknis.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $tahun = (int) date('Y');

        $kampungList = collect(['Kampung Contoh Satu', 'Kampung Contoh Dua', 'Kampung Contoh Tiga'])
            ->map(fn ($nama, $i) => Kampung::create([
                'kode_kampung' => sprintf('DEMO-%02d', $i + 1),
                'nama_kampung' => $nama,
                'kecamatan' => 'Kecamatan Contoh',
                'status_aktif' => true,
            ]));

        $admin = User::create([
            'name' => 'Admin Sistem',
            'email' => 'admin@spjdanadesa.test',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        $inspektorat = User::create([
            'name' => 'Auditor Inspektorat',
            'email' => 'inspektorat@spjdanadesa.test',
            'password' => Hash::make('password'),
            'role' => User::ROLE_INSPEKTORAT,
        ]);

        $pendamping = User::create([
            'name' => 'Pendamping Desa Contoh',
            'email' => 'pendamping@spjdanadesa.test',
            'password' => Hash::make('password'),
            'role' => User::ROLE_PENDAMPING,
        ]);

        foreach ($kampungList as $kampung) {
            PendampingWilayah::create(['user_id' => $pendamping->id, 'kampung_id' => $kampung->id]);

            User::create([
                'kampung_id' => $kampung->id,
                'name' => "Kaur Keuangan {$kampung->nama_kampung}",
                'email' => 'kaur.'.strtolower(str_replace(['-', ' '], '', $kampung->kode_kampung)).'@spjdanadesa.test',
                'password' => Hash::make('password'),
                'role' => User::ROLE_KAUR_KEUANGAN,
            ]);

            User::create([
                'kampung_id' => $kampung->id,
                'name' => "Kepala {$kampung->nama_kampung}",
                'email' => 'kepala.'.strtolower(str_replace(['-', ' '], '', $kampung->kode_kampung)).'@spjdanadesa.test',
                'password' => Hash::make('password'),
                'role' => User::ROLE_KEPALA_KAMPUNG,
            ]);
        }

        // Bidang kegiatan APBDes mengikuti 5 klasifikasi standar Permendagri/Siskeudes.
        $bidang = collect([
            ['kode' => '1', 'nama_bidang' => 'Penyelenggaraan Pemerintahan Desa'],
            ['kode' => '2', 'nama_bidang' => 'Pelaksanaan Pembangunan Desa'],
            ['kode' => '3', 'nama_bidang' => 'Pembinaan Kemasyarakatan Desa'],
            ['kode' => '4', 'nama_bidang' => 'Pemberdayaan Masyarakat Desa'],
            ['kode' => '5', 'nama_bidang' => 'Penanggulangan Bencana, Keadaan Darurat dan Mendesak Desa'],
        ])->map(fn ($item) => BidangKegiatan::create([...$item, 'tahun_anggaran' => $tahun]));

        // Kode rekening belanja contoh mengikuti pola Siskeudes V2.0 (Bab III.1).
        $kodeRekening = collect([
            ['kode' => '5.1.3.01', 'uraian' => 'Penghasilan Tetap Kepala Desa dan Perangkat', 'jenis_belanja' => KodeRekening::JENIS_PEGAWAI],
            ['kode' => '5.2.1.01', 'uraian' => 'Belanja Alat Tulis Kantor', 'jenis_belanja' => KodeRekening::JENIS_BARANG_JASA],
            ['kode' => '5.2.2.01', 'uraian' => 'Belanja Honorarium Non PNS', 'jenis_belanja' => KodeRekening::JENIS_BARANG_JASA],
            ['kode' => '5.3.1.01', 'uraian' => 'Belanja Modal Pengadaan Sarana Prasarana', 'jenis_belanja' => KodeRekening::JENIS_MODAL],
            ['kode' => '5.4.1.01', 'uraian' => 'Belanja Tak Terduga', 'jenis_belanja' => KodeRekening::JENIS_TAK_TERDUGA],
        ])->map(fn ($item) => KodeRekening::create([...$item, 'tahun_anggaran' => $tahun]));

        foreach ($kampungList as $kampung) {
            $kegiatan = Kegiatan::create([
                'kampung_id' => $kampung->id,
                'bidang_kegiatan_id' => $bidang->first()->id,
                'nama_kegiatan' => 'Operasional Perkantoran Kampung',
                'tahun_anggaran' => $tahun,
                'pagu_total' => 50_000_000,
            ]);

            $kegiatan->paguRekening()->create([
                'kode_rekening_id' => $kodeRekening->firstWhere('kode', '5.2.1.01')->id,
                'pagu_anggaran' => 20_000_000,
            ]);

            $kegiatan->paguRekening()->create([
                'kode_rekening_id' => $kodeRekening->firstWhere('kode', '5.2.2.01')->id,
                'pagu_anggaran' => 30_000_000,
            ]);
        }

        $this->command?->info('Seeder selesai. Login demo (password: "password"):');
        $this->command?->table(['Role', 'Email'], [
            ['admin', $admin->email],
            ['inspektorat', $inspektorat->email],
            ['pendamping', $pendamping->email],
            ['kaur_keuangan', 'kaur.demo01@spjdanadesa.test'],
            ['kepala_kampung', 'kepala.demo01@spjdanadesa.test'],
        ]);
    }
}
