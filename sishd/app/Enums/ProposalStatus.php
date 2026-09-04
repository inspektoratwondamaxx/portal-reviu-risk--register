<?php

namespace App\Enums;

/** Status usulan OPD (Bab 11 kajian): MENUNGGU -> SETUJU/REVISI/TOLAK -> (revisi kembali ke menunggu). */
enum ProposalStatus: string
{
    case Draft = 'draft';
    case MenungguVerifikasi = 'menunggu_verifikasi';
    case Revisi = 'revisi';
    case Disetujui = 'disetujui';
    case Ditolak = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::MenungguVerifikasi => 'Menunggu Verifikasi',
            self::Revisi => 'Revisi',
            self::Disetujui => 'Disetujui',
            self::Ditolak => 'Ditolak',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'bg-secondary',
            self::MenungguVerifikasi => 'bg-warning text-dark',
            self::Revisi => 'bg-info text-dark',
            self::Disetujui => 'bg-success',
            self::Ditolak => 'bg-danger',
        };
    }
}
