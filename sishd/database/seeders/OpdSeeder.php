<?php

namespace Database\Seeders;

use App\Models\Opd;
use Illuminate\Database\Seeder;

class OpdSeeder extends Seeder
{
    public function run(): void
    {
        $opds = [
            ['kode' => '1.02', 'nama' => 'Dinas Pekerjaan Umum dan Tata Ruang', 'singkatan' => 'DPUTR'],
            ['kode' => '1.03', 'nama' => 'Dinas Perumahan dan Kawasan Permukiman', 'singkatan' => 'Disperkim'],
            ['kode' => '1.04', 'nama' => 'Dinas Kesehatan', 'singkatan' => 'Dinkes'],
            ['kode' => '1.05', 'nama' => 'Dinas Pendidikan', 'singkatan' => 'Disdik'],
            ['kode' => '1.06', 'nama' => 'Badan Perencanaan Pembangunan Daerah', 'singkatan' => 'Bappeda'],
            ['kode' => '1.07', 'nama' => 'Badan Pengelolaan Keuangan dan Aset Daerah', 'singkatan' => 'BPKAD'],
            ['kode' => '1.08', 'nama' => 'Inspektorat Daerah', 'singkatan' => 'Inspektorat'],
            ['kode' => '1.09', 'nama' => 'Dinas Komunikasi dan Informatika', 'singkatan' => 'Diskominfo'],
        ];

        foreach ($opds as $opd) {
            Opd::updateOrCreate(['kode' => $opd['kode']], $opd + ['is_active' => true]);
        }
    }
}
