<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Akun contoh untuk percobaan (lokal). Ganti kata sandi sebelum dipakai sungguhan.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('Seeder akun contoh tidak dijalankan di production.');

            return;
        }

        $akun = [
            ['Admin Sistem', 'admin@contoh.test', 'admin', 'Administrator'],
            ['Petugas Persuratan', 'operator@contoh.test', 'operator', 'Pengelola Persuratan'],
            ['Kepala Dinas', 'pimpinan@contoh.test', 'pimpinan', 'Kepala'],
            ['Staf Bidang', 'staf@contoh.test', 'staf', 'Staf'],
        ];

        foreach ($akun as [$nama, $email, $role, $jabatan]) {
            User::updateOrCreate(['email' => $email], [
                'name' => $nama, 'role' => $role, 'jabatan' => $jabatan, 'password' => 'password',
            ]);
        }
    }
}
