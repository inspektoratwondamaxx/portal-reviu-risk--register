<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Lapisan isolasi multi-tenant di level aplikasi (KNF-04), melengkapi Row-Level Security di
 * basis data (Bab V.4). kaur_keuangan/kepala_kampung hanya melihat baris kampung sendiri,
 * pendamping dibatasi pada kampung_id di tabel pendamping_wilayah, sedangkan inspektorat dan
 * admin memiliki akses lintas kampung.
 */
trait BelongsToKampung
{
    public static function bootBelongsToKampung(): void
    {
        static::addGlobalScope('kampung', function (Builder $builder) {
            $user = Auth::user();

            if (! $user) {
                return;
            }

            if (in_array($user->role, ['inspektorat', 'admin'], true)) {
                return;
            }

            $table = $builder->getModel()->getTable();

            if ($user->role === 'pendamping') {
                $builder->whereIn("{$table}.kampung_id", $user->pendampingWilayah()->pluck('kampung_id'));

                return;
            }

            $builder->where("{$table}.kampung_id", $user->kampung_id);
        });

        static::creating(function ($model) {
            $user = Auth::user();

            if ($user && ! $model->kampung_id && $user->kampung_id) {
                $model->kampung_id = $user->kampung_id;
            }
        });
    }
}
