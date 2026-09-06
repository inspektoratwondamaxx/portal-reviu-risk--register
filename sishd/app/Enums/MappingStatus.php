<?php

namespace App\Enums;

/** Status validasi code_mappings (Bab 10 kajian). */
enum MappingStatus: string
{
    case Valid = 'valid';
    case BelumRekening = 'belum_rekening';
    case Duplikasi = 'duplikasi';
    case TidakDitemukan = 'tidak_ditemukan';

    public function label(): string
    {
        return match ($this) {
            self::Valid => 'Kode valid',
            self::BelumRekening => 'Belum memiliki rekening',
            self::Duplikasi => 'Duplikasi',
            self::TidakDitemukan => 'Kode tidak ditemukan',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Valid => '✅',
            self::BelumRekening, self::Duplikasi => '⚠️',
            self::TidakDitemukan => '❌',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Valid => 'bg-success',
            self::BelumRekening, self::Duplikasi => 'bg-warning text-dark',
            self::TidakDitemukan => 'bg-danger',
        };
    }
}
