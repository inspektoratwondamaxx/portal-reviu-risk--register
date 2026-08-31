<?php

namespace Database\Seeders;

use App\Models\AccountCode;
use App\Models\AssetCode;
use App\Models\AssetGroup;
use App\Models\Category;
use App\Models\CodeMapping;
use App\Models\SipdCode;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

/** Data referensi: kategori, kelompok barang, kode aset, kode rekening (BAS), kode SIPD, mapping, penyedia. */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $material = Category::updateOrCreate(['kode' => 'MAT', 'jenis' => 'material'], ['nama' => 'Material Konstruksi', 'jenis' => 'material']);
        Category::updateOrCreate(['kode' => 'MAT-01', 'jenis' => 'material'], ['parent_id' => $material->id, 'nama' => 'Semen & Perekat', 'jenis' => 'material']);
        Category::updateOrCreate(['kode' => 'MAT-02', 'jenis' => 'material'], ['parent_id' => $material->id, 'nama' => 'Besi & Baja', 'jenis' => 'material']);
        Category::updateOrCreate(['kode' => 'MAT-03', 'jenis' => 'material'], ['parent_id' => $material->id, 'nama' => 'Agregat (Pasir, Batu, Kerikil)', 'jenis' => 'material']);
        $upah = Category::updateOrCreate(['kode' => 'UPH', 'jenis' => 'upah'], ['nama' => 'Upah Tenaga Kerja', 'jenis' => 'upah']);
        $alat = Category::updateOrCreate(['kode' => 'ALT', 'jenis' => 'peralatan'], ['nama' => 'Peralatan & Sewa Alat', 'jenis' => 'peralatan']);
        Category::updateOrCreate(['kode' => 'ATK', 'jenis' => 'lainnya'], ['nama' => 'Alat Tulis Kantor', 'jenis' => 'lainnya']);
        Category::updateOrCreate(['kode' => 'JSA', 'jenis' => 'jasa'], ['nama' => 'Jasa Konsultansi', 'jenis' => 'jasa']);

        $groupMaterial = AssetGroup::updateOrCreate(['kode' => '1.3.01'], ['nama' => 'Material', 'keterangan' => 'Kelompok barang habis pakai/material konstruksi']);
        $groupPeralatan = AssetGroup::updateOrCreate(['kode' => '1.3.02'], ['nama' => 'Peralatan', 'keterangan' => 'Kelompok peralatan & mesin']);
        $groupJasa = AssetGroup::updateOrCreate(['kode' => '1.3.03'], ['nama' => 'Jasa', 'keterangan' => 'Kelompok belanja jasa']);

        $akunBelanja = AccountCode::updateOrCreate(['kode' => '5'], ['uraian' => 'Belanja Daerah', 'level' => 1]);
        $belanjaOperasi = AccountCode::updateOrCreate(['kode' => '5.1'], ['parent_id' => $akunBelanja->id, 'uraian' => 'Belanja Operasi', 'level' => 2]);
        $rekMaterial = AccountCode::updateOrCreate(['kode' => '5.1.02.01.01'], ['parent_id' => $belanjaOperasi->id, 'uraian' => 'Belanja Bahan Material', 'jenis_belanja' => 'Barang dan Jasa', 'level' => 5]);
        $rekPeralatan = AccountCode::updateOrCreate(['kode' => '5.1.02.02.01'], ['parent_id' => $belanjaOperasi->id, 'uraian' => 'Belanja Peralatan', 'jenis_belanja' => 'Barang dan Jasa', 'level' => 5]);
        $rekJasa = AccountCode::updateOrCreate(['kode' => '5.1.02.03.01'], ['parent_id' => $belanjaOperasi->id, 'uraian' => 'Belanja Jasa Kantor', 'jenis_belanja' => 'Barang dan Jasa', 'level' => 5]);

        $sipdMaterial = SipdCode::updateOrCreate(['kode' => 'SIPD-5.1.02.01.01'], ['uraian' => 'Belanja Bahan Material', 'tipe' => 'ssh', 'account_code_id' => $rekMaterial->id]);
        $sipdPeralatan = SipdCode::updateOrCreate(['kode' => 'SIPD-5.1.02.02.01'], ['uraian' => 'Belanja Peralatan', 'tipe' => 'ssh', 'account_code_id' => $rekPeralatan->id]);
        SipdCode::updateOrCreate(['kode' => 'SIPD-5.1.02.03.01'], ['uraian' => 'Belanja Jasa Kantor', 'tipe' => 'sbu', 'account_code_id' => $rekJasa->id]);

        $assetCodes = [
            ['1.3.01.01', $groupMaterial->id, 'Semen & Bahan Perekat', $rekMaterial->id, $sipdMaterial->id],
            ['1.3.01.02', $groupMaterial->id, 'Besi & Baja Konstruksi', $rekMaterial->id, $sipdMaterial->id],
            ['1.3.01.03', $groupMaterial->id, 'Agregat (Pasir/Batu/Kerikil)', $rekMaterial->id, $sipdMaterial->id],
            ['1.3.02.01', $groupPeralatan->id, 'Alat Berat & Sewa Alat', $rekPeralatan->id, $sipdPeralatan->id],
            ['1.3.03.01', $groupJasa->id, 'Jasa Konsultansi Perencanaan', null, null],
        ];

        foreach ($assetCodes as [$kode, $groupId, $nama, $accountCodeId, $sipdCodeId]) {
            $assetCode = AssetCode::updateOrCreate(['kode' => $kode], ['asset_group_id' => $groupId, 'nama' => $nama]);

            CodeMapping::updateOrCreate(
                ['asset_code_id' => $assetCode->id],
                [
                    'account_code_id' => $accountCodeId,
                    'sipd_code_id' => $sipdCodeId,
                    'status' => $accountCodeId ? 'valid' : 'belum_rekening',
                    'checked_at' => now(),
                ]
            );
        }

        $vendors = [
            ['nama' => 'Toko Bangunan Sumber Jaya', 'kecamatan' => 'Kota', 'telepon' => '031-7001234'],
            ['nama' => 'UD Makmur Material', 'kecamatan' => 'Gresik', 'telepon' => '031-7005678'],
            ['nama' => 'CV Bangun Sejahtera', 'kecamatan' => 'Kebomas', 'telepon' => '031-7009012'],
        ];

        foreach ($vendors as $vendor) {
            Vendor::updateOrCreate(['nama' => $vendor['nama']], $vendor + ['is_active' => true]);
        }
    }
}
