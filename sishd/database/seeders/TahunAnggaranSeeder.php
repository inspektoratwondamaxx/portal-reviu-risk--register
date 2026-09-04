<?php

namespace Database\Seeders;

use App\Models\TahunAnggaran;
use Illuminate\Database\Seeder;

class TahunAnggaranSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([2024, 2025] as $tahun) {
            TahunAnggaran::updateOrCreate(['tahun' => $tahun], ['status' => 'tutup', 'is_active' => false]);
        }

        TahunAnggaran::updateOrCreate(
            ['tahun' => 2026],
            ['status' => 'aktif', 'is_active' => true, 'tanggal_mulai' => '2026-01-01', 'tanggal_selesai' => '2026-12-31']
        );
    }
}
