<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Opd;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Akun demo — kata sandi seragam "password" untuk kemudahan uji coba. WAJIB diganti sebelum
 * dipakai produksi sungguhan.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $dinasPu = Opd::where('kode', '1.02')->first();
        $inspektorat = Opd::where('kode', '1.08')->first();

        $accounts = [
            ['inspektoratwondamaxx@gmail.com', 'Super Admin SISHD', RoleName::SuperAdmin, $inspektorat?->id],
            ['adminssh@sishd.test', 'Admin SSH', RoleName::AdminSsh, null],
            ['adminhspk@sishd.test', 'Admin HSPK/ASB', RoleName::AdminHspkAsb, null],
            ['operator@sishd.test', 'Operator Dinas PU', RoleName::OpdOperator, $dinasPu?->id],
            ['verifikator@sishd.test', 'Verifikator Standar Harga', RoleName::Verifikator, null],
            ['timstandarharga@sishd.test', 'Tim Standar Harga', RoleName::TimStandarHarga, null],
            ['pejabat@sishd.test', 'Pejabat Berwenang', RoleName::PejabatBerwenang, null],
            ['pimpinan@sishd.test', 'Pimpinan', RoleName::Pimpinan, null],
        ];

        foreach ($accounts as [$email, $name, $roleName, $opdId]) {
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'role_id' => Role::where('name', $roleName->value)->value('id'),
                    'opd_id' => $opdId,
                    'password' => 'password',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
