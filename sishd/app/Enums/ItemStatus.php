<?php

namespace App\Enums;

/**
 * Status lifecycle SSH/SBU/HSPK/ASB (Bab 12 kajian desain):
 * draft -> diajukan -> verifikasi -> disetujui -> aktif, dengan cabang ditolak/nonaktif.
 * Data tidak pernah dihapus langsung, hanya dinonaktifkan agar histori standar harga tetap tersedia.
 */
enum ItemStatus: string
{
    case Draft = 'draft';
    case Diajukan = 'diajukan';
    case Verifikasi = 'verifikasi';
    case Disetujui = 'disetujui';
    case Aktif = 'aktif';
    case Ditolak = 'ditolak';
    case Nonaktif = 'nonaktif';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Diajukan => 'Diajukan',
            self::Verifikasi => 'Verifikasi',
            self::Disetujui => 'Disetujui',
            self::Aktif => 'Aktif',
            self::Ditolak => 'Ditolak',
            self::Nonaktif => 'Nonaktif',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'bg-secondary',
            self::Diajukan, self::Verifikasi => 'bg-warning text-dark',
            self::Disetujui, self::Aktif => 'bg-success',
            self::Ditolak => 'bg-danger',
            self::Nonaktif => 'bg-dark',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return array_combine(
            array_map(fn (self $case) => $case->value, self::cases()),
            array_map(fn (self $case) => $case->label(), self::cases()),
        );
    }
}
