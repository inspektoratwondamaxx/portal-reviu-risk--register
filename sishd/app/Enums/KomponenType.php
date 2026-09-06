<?php

namespace App\Enums;

/** Tiga jenis komponen pembentuk HSPK (Bab 8 kajian). */
enum KomponenType: string
{
    case Material = 'material';
    case TenagaKerja = 'tenaga_kerja';
    case Peralatan = 'peralatan';

    public function label(): string
    {
        return match ($this) {
            self::Material => 'Material',
            self::TenagaKerja => 'Tenaga Kerja',
            self::Peralatan => 'Peralatan',
        };
    }
}
