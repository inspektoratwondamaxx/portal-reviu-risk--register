<?php

namespace App\Policies;

use App\Models\Transaksi;
use App\Models\User;

/**
 * Bab VI.7 kajian teknis: kaur_keuangan hanya boleh mengakses transaksi kampung_id miliknya;
 * pendamping dibatasi pada kampung yang terdaftar di pendamping_wilayah; inspektorat/admin
 * lintas kampung.
 */
class TransaksiPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Transaksi $transaksi): bool
    {
        return $this->punyaAksesKampung($user, $transaksi->kampung_id);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_KAUR_KEUANGAN);
    }

    /** Hanya boleh diedit Kaur Keuangan pemilik kampung selama status masih draft/revisi. */
    public function update(User $user, Transaksi $transaksi): bool
    {
        return $user->hasRole(User::ROLE_KAUR_KEUANGAN)
            && $user->kampung_id === $transaksi->kampung_id
            && in_array($transaksi->status, [Transaksi::STATUS_DRAFT, Transaksi::STATUS_REVISI], true);
    }

    public function uploadBukti(User $user, Transaksi $transaksi): bool
    {
        return $this->update($user, $transaksi);
    }

    public function ajukan(User $user, Transaksi $transaksi): bool
    {
        return $user->hasRole(User::ROLE_KAUR_KEUANGAN)
            && $user->kampung_id === $transaksi->kampung_id
            && $transaksi->status === Transaksi::STATUS_TERVERIFIKASI;
    }

    public function verifikasiOcr(User $user, Transaksi $transaksi): bool
    {
        return $this->update($user, $transaksi);
    }

    private function punyaAksesKampung(User $user, ?int $kampungId): bool
    {
        return match ($user->role) {
            User::ROLE_INSPEKTORAT, User::ROLE_ADMIN => true,
            User::ROLE_PENDAMPING => $user->kampungBinaan()->where('kampungs.id', $kampungId)->exists(),
            default => $user->kampung_id === $kampungId,
        };
    }
}
