<?php

namespace App\Enums;

/** 6 level akses (Bab 17 kajian). Publik tidak pernah login — tidak punya baris users. */
enum RoleName: string
{
    case SuperAdmin = 'super_admin';
    case AdminSsh = 'admin_ssh';
    case AdminHspkAsb = 'admin_hspk_asb';
    case OpdOperator = 'opd_operator';
    case Verifikator = 'verifikator';
    case Publik = 'publik';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::AdminSsh => 'Admin SSH',
            self::AdminHspkAsb => 'Admin HSPK/ASB',
            self::OpdOperator => 'OPD/Operator',
            self::Verifikator => 'Verifikator',
            self::Publik => 'Publik',
        };
    }
}
