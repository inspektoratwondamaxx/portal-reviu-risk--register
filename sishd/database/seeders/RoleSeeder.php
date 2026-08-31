<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [RoleName::SuperAdmin, 'Seluruh akses sistem: master data, manajemen user, konfigurasi, audit log.'],
            [RoleName::AdminSsh, 'Mengelola Master SSH & SBU, import/export, mapping kode.'],
            [RoleName::AdminHspkAsb, 'Menyusun analisis HSPK (komponen material/tenaga/peralatan) dan ASB (formula & variabel).'],
            [RoleName::OpdOperator, 'Mengajukan usulan data baru/perubahan dan melihat status usulan OPD-nya.'],
            [RoleName::Verifikator, 'Memeriksa dan memverifikasi usulan OPD sebelum disetujui/aktif.'],
            [RoleName::Publik, 'Melihat dan mencari data standar harga yang telah dipublikasikan (tanpa login).'],
        ];

        foreach ($roles as [$name, $description]) {
            Role::updateOrCreate(
                ['name' => $name->value],
                ['label' => $name->label(), 'description' => $description]
            );
        }
    }
}
