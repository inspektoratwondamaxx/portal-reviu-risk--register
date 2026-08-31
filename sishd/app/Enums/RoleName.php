<?php

namespace App\Enums;

/**
 * 6 level akses inti (Bab 17 kajian) plus 3 role tambahan versi 2026 (Bab 22.3 & 22.7 kajian):
 * approval berjenjang (Tim Standar Harga, Pejabat Berwenang) dan dashboard pimpinan.
 * Publik tidak pernah login — tidak punya baris users.
 */
enum RoleName: string
{
    case SuperAdmin = 'super_admin';
    case AdminSsh = 'admin_ssh';
    case AdminHspkAsb = 'admin_hspk_asb';
    case OpdOperator = 'opd_operator';
    case Verifikator = 'verifikator';
    case TimStandarHarga = 'tim_standar_harga';
    case PejabatBerwenang = 'pejabat_berwenang';
    case Pimpinan = 'pimpinan';
    case Publik = 'publik';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::AdminSsh => 'Admin SSH',
            self::AdminHspkAsb => 'Admin HSPK/ASB',
            self::OpdOperator => 'OPD/Operator',
            self::Verifikator => 'Verifikator',
            self::TimStandarHarga => 'Tim Standar Harga',
            self::PejabatBerwenang => 'Pejabat Berwenang',
            self::Pimpinan => 'Pimpinan',
            self::Publik => 'Publik',
        };
    }
}
