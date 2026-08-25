<?php

namespace App\Policies;

use App\Models\PeriodeSpj;
use App\Models\User;

/**
 * Alur persetujuan berjenjang (KF-14): kepala_kampung -> pendamping -> inspektorat.
 * Tiap peran hanya bisa menyetujui/menolak pada tahap yang menjadi wewenangnya.
 */
class PeriodeSpjPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PeriodeSpj $periode): bool
    {
        return $this->punyaAksesKampung($user, $periode->kampung_id);
    }

    public function ajukan(User $user, PeriodeSpj $periode): bool
    {
        return $user->hasRole(User::ROLE_KAUR_KEUANGAN)
            && $user->kampung_id === $periode->kampung_id
            && $periode->status === PeriodeSpj::STATUS_PROSES;
    }

    /**
     * Catatan: tabel peran Bab IV.3 menyebut kepala_kampung "menyetujui SPJ tingkat internal
     * kampung", namun tabel endpoint Bab VI.5 hanya memberi akses POST .../setujui ke
     * pendamping & inspektorat. Implementasi mengikuti Bab VI.5 (spesifikasi API, lebih rinci);
     * persetujuan internal kepala_kampung direkomendasikan diklarifikasi ke pemilik kebutuhan
     * sebelum Tahap 2 — lihat README bagian "Catatan Ambiguitas Dokumen".
     */
    public function setujui(User $user, PeriodeSpj $periode): bool
    {
        return match (true) {
            $user->hasRole(User::ROLE_PENDAMPING) => $user->kampungBinaan()->where('kampungs.id', $periode->kampung_id)->exists()
                    && $periode->status === PeriodeSpj::STATUS_DIAJUKAN,
            $user->hasRole(User::ROLE_INSPEKTORAT) => $periode->status === PeriodeSpj::STATUS_DISETUJUI_PENDAMPING,
            default => false,
        };
    }

    public function tolak(User $user, PeriodeSpj $periode): bool
    {
        return $this->setujui($user, $periode);
    }

    public function generatePdf(User $user, PeriodeSpj $periode): bool
    {
        return $this->punyaAksesKampung($user, $periode->kampung_id)
            && $user->hasRole(User::ROLE_KAUR_KEUANGAN, User::ROLE_INSPEKTORAT);
    }

    public function exportSiskeudes(User $user, PeriodeSpj $periode): bool
    {
        return $user->hasRole(User::ROLE_INSPEKTORAT, User::ROLE_ADMIN);
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
